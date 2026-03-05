<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { timeGreetings, ClientListDataInterface, dateView } from '../AppCommon';
import { useEchoStore } from '../AppState';
import { storeToRefs } from 'pinia';
import { api } from '../AppAxios';
import CmpToast from '../Components/CmpToast.vue';
import CmpLayout from '../Components/CmpLayout.vue';
import CmpCustomTable from '../Components/CmpCustomTable.vue';
import StdButton from '../Components/StdButton.vue';
import { useTableSort } from '../composables/useTableSort';

import DialogClientMan from '../DialogComponents/DialogClientMan.vue';
import type { EchoWithMethods } from '../types/echo';

const echo = useEchoStore();
const { laravelEcho } = storeToRefs(echo);
const timeGreet = timeGreetings();
const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);

const clientListData = ref(Array<ClientListDataInterface>());
const loading = ref<boolean>(false);

const q = ref('');
const page = ref(1);
const pageCount = 10;
const columns = [
    { id: 'actions', key: 'actions', label: 'Actions' },
    { id: 'name', key: 'name', label: 'Client Name', sortable: true },
    { id: 'redirect', key: 'redirect', label: 'Redirect URL', sortable: true },
    { id: 'grant_types', key: 'grant_types', label: 'Grant Types' },
    { id: 'revoked', key: 'revoked', label: 'Revoked', sortable: true },
    { id: 'created_at', key: 'created_at', label: 'Created At', sortable: true },
    { id: 'updated_at', key: 'updated_at', label: 'Updated At', sortable: true },
];

const filteredRows = computed(() => {
    if (!q.value) {
        return clientListData.value;
    }

    return clientListData.value.filter((client) => {
        return Object.values(client).some((value) => {
            return String(value).toLowerCase().includes(q.value.toLowerCase());
        });
    });
});

// Use the sorting composable
const { sortBy, sortedData } = useTableSort(filteredRows);

const getClientListData = async () => {
    try {
        loading.value = true;
        const response = await api.postGetOauthClient();
        clientListData.value = response.data as unknown as ClientListDataInterface[];
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    } finally {
        loading.value = false;
    }
};

const dialogOpen = ref<boolean>(false);
const dialogData = ref<ClientListDataInterface | null>(null);
const dialogHeader = ref<string>('Create Client');

const openEditClientDialog = (data: ClientListDataInterface | null) => {
    dialogOpen.value = true;
    dialogData.value = data;
    if (data === null) {
        dialogHeader.value = 'Create Client';
    } else {
        dialogHeader.value = 'Edit Client';
    }
};

onMounted(() => {
    getClientListData();

    // Subscribe to oauth client create/update/delete events on settings.event
    try {
        const echoInstance = laravelEcho.value as unknown as EchoWithMethods | undefined;
        if (echoInstance && typeof echoInstance.private === 'function') {
            const ch = echoInstance.private('settings.event');
            ch.listen('OauthClientCreated', () => getClientListData());
            ch.listen('OauthClientUpdated', () => getClientListData());
            ch.listen('OauthClientDeleted', () => getClientListData());
        }
    } catch (err) {
        console.debug('Echo private channel not available during mount.', err);
    }
});

onUnmounted(() => {
    try {
        const echoInstance = laravelEcho.value as unknown as EchoWithMethods | undefined;
        if (echoInstance && typeof echoInstance.leave === 'function') {
            echoInstance.leave('settings.event');
        }
    } catch (err) {
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
                                {{ dialogHeader }}
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
                    <DialogClientMan
                        v-model:dialogOpen="dialogOpen"
                        :dialogData="dialogData"
                        :dialogTypeCreate="dialogData === null ? true : false"
                    />
                </UCard>
            </template>
        </UModal>
        <div class="mx-auto w-full max-w-6xl space-y-5">
            <div
                class="rounded-xl border border-gray-200 bg-gradient-to-r from-white to-gray-50 p-6 shadow-sm"
            >
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-4">
                    <div class="flex-1">
                        <h2 class="text-4xl font-semibold tracking-tight text-gray-800">
                            {{ timeGreet }}
                        </h2>
                        <h3 class="mt-1 text-sm text-gray-600">Passport Management</h3>
                    </div>
                    <div class="flex w-full justify-end gap-2 md:w-auto">
                        <StdButton
                            variant="neutral"
                            icon="i-heroicons-arrow-path"
                            @click="getClientListData"
                        />
                        <StdButton
                            variant="primary"
                            label="Create Client"
                            class="rounded-md bg-green-600 px-5 py-2.5 text-white hover:bg-green-700"
                            @click="openEditClientDialog(null)"
                        />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center">
                    <div class="flex-1">
                        <h3 class="text-4xl font-bold text-gray-900">Client Management</h3>
                    </div>
                    <div class="w-full md:w-80">
                        <UInput v-model="q" placeholder="Search by client name..." />
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
                        <div v-if="row.allowed_action" class="flex justify-center">
                            <UButton
                                size="xl"
                                icon="i-heroicons-chevron-double-right"
                                class="bg-green-600 text-white hover:bg-green-700"
                                @click="openEditClientDialog(row)"
                            />
                        </div>
                    </template>
                    <template #name-data="{ row }">
                        <div class="text-left">{{ row.name }}</div>
                    </template>
                    <template #redirect-data="{ row }">
                        <div class="text-left">{{ row.redirect }}</div>
                    </template>
                    <template #grant_types-data="{ row }">
                        <div class="flex flex-wrap gap-1 justify-start">
                            <span
                                v-for="type in row.grant_types"
                                :key="type"
                                class="px-2 py-1 rounded bg-blue-100 text-blue-800 text-xs"
                            >
                                {{ type }}
                            </span>
                        </div>
                    </template>
                    <template #revoked-data="{ row }">
                        <div class="text-left">
                            <UBadge v-if="row.revoked" color="red" label="Yes" size="md" />
                            <UBadge v-else color="green" label="No" size="md" />
                        </div>
                    </template>
                    <template #created_at-data="{ row }">
                        <div class="text-left">
                            {{ dateView(row.created_at) }}
                        </div>
                    </template>
                    <template #updated_at-data="{ row }">
                        <div class="text-left">
                            {{ dateView(row.updated_at) }}
                        </div>
                    </template>
                </CmpCustomTable>
            </div>
        </div>
    </CmpLayout>
</template>
