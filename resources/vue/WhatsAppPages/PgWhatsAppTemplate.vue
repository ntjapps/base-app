<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useEchoStore } from '../AppState';
import { storeToRefs } from 'pinia';
import { api } from '../AppAxios';
import CmpToast from '../Components/CmpToast.vue';
import CmpLayout from '../Components/CmpLayout.vue';
import CmpTemplateDetail from './CmpTemplateDetail.vue';
import CmpCustomTable from '../Components/CmpCustomTable.vue';
import StdButton from '../Components/StdButton.vue';
import { useTableSort } from '../composables/useTableSort';

const echo = useEchoStore();
const { laravelEcho } = storeToRefs(echo);
const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);

interface WaTemplate {
    id: string;
    name: string;
    status?: string | null;
    category?: string | null;
    language?: string | null;
    components?: unknown[] | null;
    correct_category?: string | null;
    cta_url_link_tracking_opted_out?: boolean | null;
    degrees_of_freedom_spec?: unknown | null;
    library_template_name?: string | null;
    message_send_ttl_seconds?: number | null;
    parameter_format?: string | null;
    previous_category?: string | null;
    quality_score?: unknown | null;
    rejected_reason?: string | null;
    sub_category?: string | null;
    created_at?: string | null;
}

const templateListData = ref<WaTemplate[]>([]);

const loading = ref<boolean>(false);

const q = ref('');
const page = ref(1);
const pageCount = 10;

const columns = [
    { id: 'actions', key: 'actions', label: 'Actions' },
    { id: 'name', key: 'name', label: 'Template Name', sortable: true },
    { id: 'category', key: 'category', label: 'Category', sortable: true },
    { id: 'language', key: 'language', label: 'Language', sortable: true },
    { id: 'status', key: 'status', label: 'Status', sortable: true },
    { id: 'rejected_reason', key: 'rejected_reason', label: 'Rejected Reason' },
];

const filteredRows = computed(() => {
    if (!q.value) {
        return templateListData.value;
    }

    return templateListData.value.filter((template) => {
        return Object.values(template).some((value) => {
            return String(value).toLowerCase().includes(q.value.toLowerCase());
        });
    });
});

// Use the sorting composable
const { sortBy, sortedData } = useTableSort(filteredRows);

type EchoChannel = {
    listen: (event: string, cb: (payload?: unknown) => void) => unknown;
};

type EchoWithMethods = {
    private: (channel: string) => EchoChannel;
    leave: (channel: string) => void;
};

type BroadcastTemplatePayload = {
    template?: {
        id?: string;
        provider_id?: string;
        name?: string;
        language?: string;
        category?: string;
        status?: string;
        [key: string]: unknown;
    };
    template_id?: string;
    template_name?: string;
    action?: string;
};

const showViewButton = (data: string | null | undefined): boolean => {
    return !!data;
};

const dialogOpen = ref<boolean>(false);
const dialogData = ref<WaTemplate | null>(null);
const dialogMode = ref<'create' | 'edit'>('create');

const openCreateDialog = () => {
    dialogMode.value = 'create';
    dialogData.value = null;
    dialogOpen.value = true;
};

const openEditDialog = (data: WaTemplate) => {
    dialogMode.value = 'edit';
    dialogData.value = data;
    dialogOpen.value = true;
};

const deleteTemplate = async (templateId: string) => {
    try {
        loading.value = true;
        await api.deleteWhatsappTemplate(templateId);
        await getTemplateListData();
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const handleTemplateCreated = (payload?: BroadcastTemplatePayload) => {
    if (!payload || !payload.template) {
        // No payload, refresh the list
        getTemplateListData();
        return;
    }

    const template = payload.template;
    const newTemplate: WaTemplate = {
        id: String(template.id ?? template.provider_id ?? ''),
        name: String(template.name ?? ''),
        status: template.status ? String(template.status) : null,
        category: template.category ? String(template.category) : null,
        language: template.language ? String(template.language) : null,
        components: Array.isArray(template.components) ? template.components : null,
    };

    // Add to the top of the list if not already present
    const exists = templateListData.value.find((t) => t.id === newTemplate.id);
    if (!exists) {
        templateListData.value.unshift(newTemplate);
    }
};

const handleTemplateUpdated = (payload?: BroadcastTemplatePayload) => {
    if (!payload || !payload.template) {
        // No payload, refresh the list
        getTemplateListData();
        return;
    }

    const template = payload.template;
    const templateId = String(template.id ?? template.provider_id ?? '');

    const index = templateListData.value.findIndex((t) => t.id === templateId);
    if (index !== -1) {
        // Update existing template
        templateListData.value[index] = {
            ...templateListData.value[index],
            name: String(template.name ?? templateListData.value[index].name),
            status: template.status
                ? String(template.status)
                : templateListData.value[index].status,
            category: template.category
                ? String(template.category)
                : templateListData.value[index].category,
            language: template.language
                ? String(template.language)
                : templateListData.value[index].language,
            components: Array.isArray(template.components)
                ? template.components
                : templateListData.value[index].components,
        };
    } else {
        // Template not in list, refresh to get it
        getTemplateListData();
    }
};

const handleTemplateDeleted = (payload?: BroadcastTemplatePayload) => {
    if (!payload) {
        // No payload, refresh the list
        getTemplateListData();
        return;
    }

    const templateId = payload.template_id || payload.template?.id || payload.template?.provider_id;
    if (templateId) {
        const index = templateListData.value.findIndex((t) => t.id === String(templateId));
        if (index !== -1) {
            templateListData.value.splice(index, 1);
        }
    }
};

onMounted(() => {
    getTemplateListData();

    // Subscribe to template events
    try {
        const echoInstance = laravelEcho.value as unknown as EchoWithMethods | undefined;
        if (echoInstance && typeof echoInstance.private === 'function') {
            const channel = echoInstance.private('whatsapp.templates');
            channel.listen('WhatsappTemplateCreated', handleTemplateCreated);
            channel.listen('WhatsappTemplateUpdated', handleTemplateUpdated);
            channel.listen('WhatsappTemplateDeleted', handleTemplateDeleted);
        }
    } catch (err) {
        console.debug('Echo private channel not available during mount.', err);
    }
});

onUnmounted(() => {
    try {
        const echoInstance = laravelEcho.value as unknown as EchoWithMethods | undefined;
        if (echoInstance && typeof echoInstance.leave === 'function') {
            echoInstance.leave('whatsapp.templates');
        }
    } catch (err) {
        console.debug('Echo leave failed during unmount.', err);
    }
});

const getTemplateListData = async () => {
    try {
        loading.value = true;
        const resp = await api.getWhatsappTemplatesList({}, { noToast: true });

        // Backend now returns { data: [...] } format consistently
        const list = Array.isArray(resp.data?.data) ? resp.data.data : [];

        templateListData.value = list.map((d) => ({
            id: String(d.id ?? ''),
            name: String(d.name ?? ''),
            status: d.status ? String(d.status) : null,
            category: d.category ? String(d.category) : null,
            language: d.language ? String(d.language) : null,
            components: Array.isArray(d.components) ? d.components : null,
            correct_category: d.correct_category ? String(d.correct_category) : null,
            cta_url_link_tracking_opted_out:
                typeof d.cta_url_link_tracking_opted_out === 'boolean'
                    ? d.cta_url_link_tracking_opted_out
                    : null,
            degrees_of_freedom_spec: d.degrees_of_freedom_spec ?? null,
            library_template_name: d.library_template_name ? String(d.library_template_name) : null,
            message_send_ttl_seconds:
                typeof d.message_send_ttl_seconds === 'number' ? d.message_send_ttl_seconds : null,
            parameter_format: d.parameter_format ? String(d.parameter_format) : null,
            previous_category: d.previous_category ? String(d.previous_category) : null,
            quality_score: d.quality_score ?? null,
            rejected_reason: d.rejected_reason ? String(d.rejected_reason) : null,
            sub_category: d.sub_category ? String(d.sub_category) : null,
            created_at: d.created_time ? String(d.created_time) : null,
        }));
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <CmpLayout>
        <CmpToast ref="toastchild" />
        <UModal
            v-model:open="dialogOpen"
            :ui="{ content: 'w-[calc(100vw-2rem)] sm:max-w-[1200px]' }"
            :title="dialogMode === 'create' ? 'Create Template' : 'Edit Template'"
            :description="
                dialogMode === 'create'
                    ? 'Create a new WhatsApp template'
                    : 'Edit an existing WhatsApp template'
            "
        >
            <template #content>
                <UCard
                    v-if="dialogOpen"
                    :ui="{ ring: '', divide: 'divide-y divide-gray-100 dark:divide-gray-800' }"
                    class="w-full h-[90vh] max-h-[800px]"
                >
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3
                                class="text-base font-semibold leading-6 text-gray-900 dark:text-white"
                            >
                                {{ dialogMode === 'create' ? 'Create Template' : 'Edit Template' }}
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
                    <CmpTemplateDetail
                        v-model:dialogOpen="dialogOpen"
                        :dialogData="dialogData"
                        :mode="dialogMode"
                        @saved="getTemplateListData"
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
                            Templates
                        </h2>
                        <h3 class="mt-1 text-sm text-gray-600">Template List</h3>
                    </div>
                    <div class="flex w-full justify-end gap-2 md:w-auto">
                        <StdButton
                            variant="neutral"
                            icon="i-heroicons-arrow-path"
                            @click="getTemplateListData"
                        />
                        <StdButton
                            variant="primary"
                            label="Create Template"
                            class="rounded-md bg-green-600 px-5 py-2.5 text-white hover:bg-green-700"
                            @click="openCreateDialog"
                        />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center">
                    <div class="flex-1">
                        <h3 class="text-4xl font-bold text-gray-900">Template Management</h3>
                    </div>
                    <div class="w-full md:w-80">
                        <UInput v-model="q" placeholder="Search templates..." />
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
                        <div v-if="showViewButton(row.id)" class="flex gap-1 justify-center">
                            <UButton
                                size="sm"
                                icon="i-heroicons-pencil"
                                class="bg-green-600 text-white hover:bg-green-700"
                                @click="openEditDialog(row)"
                            />
                            <UButton
                                size="sm"
                                color="red"
                                icon="i-heroicons-trash"
                                @click="deleteTemplate(row.id)"
                            />
                        </div>
                    </template>
                    <template #name-data="{ row }">
                        <div class="text-left">{{ row.name }}</div>
                    </template>
                    <template #category-data="{ row }">
                        <div class="text-left">{{ row.category ?? '-' }}</div>
                    </template>
                    <template #language-data="{ row }">
                        <div class="text-left">{{ row.language ?? '-' }}</div>
                    </template>
                    <template #status-data="{ row }">
                        <div class="text-left">{{ row.status ?? '-' }}</div>
                    </template>
                    <template #rejected_reason-data="{ row }">
                        <div class="text-left">{{ row.rejected_reason ?? '-' }}</div>
                    </template>
                </CmpCustomTable>
            </div>
        </div>
    </CmpLayout>
</template>
