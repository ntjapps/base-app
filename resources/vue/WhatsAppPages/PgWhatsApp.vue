<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, shallowRef, watch } from 'vue';
import { debounce } from 'lodash';
import { useEchoStore } from '../AppState';
import { storeToRefs } from 'pinia';
import { timeView } from '../AppCommon';
import { api } from '../AppAxios';
import CmpToast from '../Components/CmpToast.vue';
import CmpLayout from '../Components/CmpLayout.vue';
import CmpMessageDetail from './CmpMessageDetail.vue';
import CmpCustomTable from '../Components/CmpCustomTable.vue';
import { useTableSort } from '../composables/useTableSort';

const echo = useEchoStore();
const { laravelEcho } = storeToRefs(echo);
const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);

interface WaThread {
    id: string;
    phone_number: string;
    contact_name: string | null;
    last_message_at: string | null;
    message_preview: string | null;
    status: string;
    assigned_agent: string | null;
    needs_reply: boolean;
}

interface WaStats {
    total: number;
    open: number;
    pending: number;
    resolved: number;
}

// Minimal typed subset for Echo instance usage in this component — avoids using `any` and keeps
// the typing focused on only the API we actually call (private(), leave()).
type EchoChannel = {
    listen: (event: string, cb: (...params: unknown[]) => void) => unknown;
};

type EchoWithMethods = {
    private: (channel: string) => EchoChannel;
    leave: (channel: string) => void;
};

type BroadcastWebhook = {
    id: string;
    body?: string;
    message_id?: string;
    from?: string;
    created_at?: string;
};

type BroadcastSent = {
    id: string;
    message?: string;
    recipient_number?: string;
    created_at?: string;
};

type BroadcastThread = {
    phone_number?: string;
    id?: string;
    last_message_at?: string;
    status?: string;
};

type BroadcastPayload = {
    webhook_log?: BroadcastWebhook;
    sent_log?: BroadcastSent;
    thread?: BroadcastThread;
};

// Optimization: Use shallowRef for large lists to avoid connection depth overhead
const threadListData = shallowRef<WaThread[]>([]);
const stats = ref<WaStats>({ total: 0, open: 0, pending: 0, resolved: 0 });
const loading = ref<boolean>(false);

const unrepliedCount = computed(() => threadListData.value.filter((t) => t.needs_reply).length);

const q = ref('');
// Debounce search input to avoid excessive filtering on every keystroke
const searchInput = ref('');
watch(
    searchInput,
    debounce((newVal: string) => {
        q.value = newVal;
    }, 300),
);
const statusFilter = ref<string | null>(null);
const replyFilter = ref<boolean | null>(null);
const page = ref(1);
const pageCount = 10;

const columns = [
    { id: 'actions', key: 'actions', label: 'Actions' },
    { id: 'status', key: 'status', label: 'Status', sortable: true },
    { id: 'needs_reply', key: 'needs_reply', label: 'Reply Status', sortable: true },
    { id: 'contact_name', key: 'contact_name', label: 'Contact Name', sortable: true },
    { id: 'phone_number', key: 'phone_number', label: 'Phone Number', sortable: true },
    { id: 'last_message_at', key: 'last_message_at', label: 'Last Message At', sortable: true },
    { id: 'message_preview', key: 'message_preview', label: 'Preview' },
];

const replyStatusOptions = [
    { label: 'All', value: null },
    { label: 'Unreplied', value: true },
    { label: 'Replied', value: false },
];

const statusOptions = [
    { label: 'All', value: null },
    { label: 'Open', value: 'OPEN' },
    { label: 'Pending', value: 'PENDING_HUMAN' },
    { label: 'Resolved', value: 'RESOLVED' },
];

const filteredRows = computed(() => {
    let filtered = threadListData.value;

    // Apply search filter
    if (q.value) {
        const search = q.value.toLowerCase();
        filtered = filtered.filter((thread) => {
            return (
                thread.phone_number?.toLowerCase().includes(search) ||
                thread.contact_name?.toLowerCase().includes(search) ||
                thread.message_preview?.toLowerCase().includes(search)
            );
        });
    }

    // Apply status filter
    if (statusFilter.value) {
        filtered = filtered.filter((thread) => thread.status === statusFilter.value);
    }

    // Apply reply filter
    if (replyFilter.value !== null) {
        filtered = filtered.filter((thread) => thread.needs_reply === replyFilter.value);
    }

    return filtered;
});

// Use the sorting composable
const { sortBy, sortedData } = useTableSort(filteredRows);

const getThreadListData = async (silent = false) => {
    try {
        if (!silent) loading.value = true;
        const response = await api.getWhatsappMessagesList();
        threadListData.value = Array.isArray(response.data) ? (response.data as WaThread[]) : [];

        const statsResponse = await api.getWhatsappStats();
        stats.value = statsResponse.data as unknown as WaStats;
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    } finally {
        if (!silent) loading.value = false;
    }
};

const resolveThread = async (id: string) => {
    try {
        loading.value = true;
        await api.resolveConversation({ conversation_id: id });
        toastchild.value?.toastDisplay({
            severity: 'success',
            summary: 'Success',
            detail: 'Conversation resolved',
        });
        await getThreadListData();
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    } finally {
        loading.value = false;
    }
};

const showViewButton = (data: string | null | undefined): boolean => {
    return !!data;
};

const dialogOpen = ref<boolean>(false);
const dialogData = ref<WaThread | null>(null);

const openMessageDialog = (data: WaThread | null) => {
    dialogOpen.value = true;
    dialogData.value = data;
};

onMounted(() => {
    getThreadListData();

    // Subscribe to WhatsApp messages channel (guarded for test environments).
    try {
        // Ensure the Echo instance supports the private channel API we need
        const echoInstance = laravelEcho.value as unknown as EchoWithMethods | undefined;
        if (echoInstance && typeof echoInstance.private === 'function') {
            const ch = echoInstance.private('whatsapp.messages');
            const handleThreadEvent = (payload?: BroadcastPayload) => {
                // If no payload (legacy/test), do full refresh
                if (!payload) {
                    getThreadListData(true);
                    return;
                }

                try {
                    const thread = payload.thread;
                    const webhook = payload.webhook_log;
                    const sent = payload.sent_log;

                    const phone = thread?.phone_number || webhook?.from || sent?.recipient_number;
                    if (!phone) return;

                    const last_message_at =
                        thread?.last_message_at ||
                        webhook?.created_at ||
                        sent?.created_at ||
                        new Date().toISOString();
                    const message_preview = webhook?.body ?? sent?.message ?? null;

                    // Find existing thread by id or phone
                    const idx = threadListData.value.findIndex(
                        (t) => (thread && t.id === thread.id) || t.phone_number === phone,
                    );
                    const updated: WaThread = {
                        id:
                            (thread && thread.id) ||
                            (idx !== -1 && threadListData.value[idx].id) ||
                            'thread-' + phone,
                        phone_number: phone,
                        contact_name:
                            (idx !== -1 && threadListData.value[idx].contact_name) || null,
                        last_message_at: last_message_at,
                        message_preview: message_preview,
                        status:
                            (thread && thread.status) ||
                            (idx !== -1 && threadListData.value[idx].status) ||
                            'OPEN',
                        assigned_agent:
                            (idx !== -1 && threadListData.value[idx].assigned_agent) || null,
                        needs_reply: (idx !== -1 && threadListData.value[idx].needs_reply) || false,
                    };

                    if (idx === -1) {
                        // Insert at top (immutable update for shallowRef)
                        threadListData.value = [updated, ...threadListData.value];
                    } else {
                        // Update existing (immutable update for shallowRef)
                        const newList = [...threadListData.value];
                        newList[idx] = { ...newList[idx], ...updated };
                        threadListData.value = newList;
                    }
                } catch (err) {
                    console.debug('Failed to apply thread payload, falling back to refresh', err);
                    getThreadListData(true);
                }
            };

            ch.listen('WhatsappMessageReceived', handleThreadEvent);
            ch.listen('WhatsappMessageSent', handleThreadEvent);
        }
    } catch (err) {
        // In unit tests, Echo may not be fully initialized or configured. Don't block the app.

        console.debug('Echo private channel not available during mount.', err);
    }
});

onUnmounted(() => {
    try {
        const echoInstance = laravelEcho.value as unknown as EchoWithMethods | undefined;
        if (echoInstance && typeof echoInstance.leave === 'function') {
            echoInstance.leave('whatsapp.messages');
        }
    } catch (err) {
        // ignore errors in testing environment where echo may not be initialized

        console.debug('Echo leave failed during unmount.', err);
    }
});
</script>

<template>
    <CmpLayout>
        <CmpToast ref="toastchild" />
        <UModal
            v-model:open="dialogOpen"
            :ui="{ content: 'w-[calc(100vw-2rem)] sm:max-w-[1200px]' }"
        >
            <template #content>
                <UCard
                    v-if="dialogOpen"
                    :ui="{ ring: '', divide: 'divide-y divide-gray-100 dark:divide-gray-800' }"
                >
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3
                                class="text-base font-semibold leading-6 text-gray-900 dark:text-white"
                            >
                                Message Detail
                            </h3>
                            <UButton
                                color="gray"
                                variant="ghost"
                                icon="i-heroicons-x-mark-20-solid"
                                class="-my-1"
                                @click="dialogOpen = false"
                            />
                        </div>
                    </template>
                    <CmpMessageDetail v-model:dialogOpen="dialogOpen" :dialogData="dialogData" />
                </UCard>
            </template>
        </UModal>
        <div class="mx-auto w-full max-w-6xl space-y-5">
            <div
                class="rounded-xl border border-gray-200 bg-gradient-to-r from-white to-gray-50 p-6 shadow-sm"
            >
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-col my-auto">
                        <h2 class="text-4xl font-semibold tracking-tight text-gray-800">Inbox</h2>
                        <h3 class="mt-1 text-sm text-gray-600">Thread List</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-2 md:grid-cols-5">
                        <div
                            class="flex flex-col items-center rounded-lg border border-gray-200 bg-white p-2"
                        >
                            <span class="text-sm font-bold text-gray-500">Total</span>
                            <span class="text-xl font-bold">{{ stats.total }}</span>
                        </div>
                        <div
                            class="flex flex-col items-center rounded-lg border border-gray-200 bg-white p-2"
                        >
                            <span class="text-sm font-bold text-red-500">Unreplied</span>
                            <span class="text-xl font-bold">{{ unrepliedCount }}</span>
                        </div>
                        <div
                            class="flex flex-col items-center rounded-lg border border-gray-200 bg-white p-2"
                        >
                            <span class="text-sm font-bold text-green-500">Open</span>
                            <span class="text-xl font-bold">{{ stats.open }}</span>
                        </div>
                        <div
                            class="flex flex-col items-center rounded-lg border border-gray-200 bg-white p-2"
                        >
                            <span class="text-sm font-bold text-yellow-500">Pending</span>
                            <span class="text-xl font-bold">{{ stats.pending }}</span>
                        </div>
                        <div
                            class="flex flex-col items-center rounded-lg border border-gray-200 bg-white p-2"
                        >
                            <span class="text-sm font-bold text-gray-400">Resolved</span>
                            <span class="text-xl font-bold">{{ stats.resolved }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 border-b border-gray-200 px-3 py-3.5">
                    <UInput
                        v-model="searchInput"
                        placeholder="Search by phone, name, or message..."
                    />
                    <div class="flex gap-2">
                        <USelectMenu
                            v-model="statusFilter"
                            :options="statusOptions"
                            optionAttribute="label"
                            valueAttribute="value"
                            placeholder="Filter by Status"
                            class="w-full"
                        />
                        <USelectMenu
                            v-model="replyFilter"
                            :options="replyStatusOptions"
                            optionAttribute="label"
                            valueAttribute="value"
                            placeholder="Filter by Reply Status"
                            class="w-full"
                        />
                    </div>
                </div>

                <CmpCustomTable
                    v-model:sortBy="sortBy"
                    v-model:page="page"
                    :rows="sortedData"
                    :columns="columns"
                    :loading="loading"
                    :itemsPerPage="pageCount"
                >
                    <template #actions-data="{ row }">
                        <div v-if="showViewButton(row.id)" class="flex justify-center gap-2">
                            <UButton
                                size="xl"
                                icon="i-heroicons-chevron-double-right"
                                class="bg-green-600 text-white hover:bg-green-700"
                                @click="openMessageDialog(row)"
                            />
                            <UButton
                                v-if="row.status !== 'RESOLVED'"
                                size="xl"
                                color="gray"
                                icon="i-heroicons-check"
                                title="Resolve"
                                @click="resolveThread(row.id)"
                            />
                        </div>
                    </template>
                    <template #status-data="{ row }">
                        <div class="text-center">
                            <UBadge
                                :label="row.status"
                                :color="
                                    row.status === 'OPEN'
                                        ? 'green'
                                        : row.status === 'PENDING_HUMAN'
                                          ? 'yellow'
                                          : 'gray'
                                "
                                size="md"
                            />
                        </div>
                    </template>
                    <template #needs_reply-data="{ row }">
                        <div class="text-center">
                            <UBadge
                                v-if="row.needs_reply"
                                color="red"
                                label="Unreplied"
                                size="md"
                            />
                            <UBadge v-else color="green" label="Replied" size="md" />
                        </div>
                    </template>
                    <template #contact_name-data="{ row }">
                        <div class="text-left">
                            {{ row.contact_name || '-' }}
                        </div>
                    </template>
                    <template #phone_number-data="{ row }">
                        <div class="text-left">
                            {{ row.phone_number }}
                        </div>
                    </template>
                    <template #last_message_at-data="{ row }">
                        <div class="text-left">
                            {{ timeView(row.last_message_at) || '-' }}
                        </div>
                    </template>
                    <template #message_preview-data="{ row }">
                        <div class="text-left">
                            {{ row.message_preview ?? '-' }}
                        </div>
                    </template>
                </CmpCustomTable>
            </div>
        </div>
    </CmpLayout>
</template>
