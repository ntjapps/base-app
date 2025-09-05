<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useMainStore } from '../AppState';
import { api } from '../AppAxios';
import CmpToast from '../Components/CmpToast.vue';
import CmpLayout from '../Components/CmpLayout.vue';
import Dialog from '../volt/Dialog.vue';
import CmpTemplateDetail from './CmpTemplateDetail.vue';
import DataTable from '../volt/DataTable.vue';
import Column from 'primevue/column';
import InputText from '../volt/InputText.vue';
import { FilterMatchMode } from '@primevue/core/api';

const props = defineProps<{
    appName: string;
    greetings: string;
    expandedKeysProps: string;
}>();

const main = useMainStore();
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

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

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

onMounted(() => {
    getTemplateListData();
    main.updateExpandedKeysMenu(props.expandedKeysProps);
});

const getTemplateListData = async () => {
    try {
        loading.value = true;
        const resp = await api.getWhatsappTemplatesList({}, { noToast: true });

        const payload = (resp.data?.data ?? resp.data) as unknown;
        if (!payload) return;

        // payload could be an array or { data: [...] }
        let list: Array<Record<string, unknown>> = [];
        if (Array.isArray(payload)) {
            list = payload as Array<Record<string, unknown>>;
        } else if (
            payload &&
            typeof payload === 'object' &&
            Array.isArray((payload as Record<string, unknown>).data)
        ) {
            list = (payload as Record<string, unknown>).data as Array<Record<string, unknown>>;
        } else {
            list = [];
        }

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
        <Dialog
            v-model:visible="dialogOpen"
            modal
            :header="dialogMode === 'create' ? 'Create Template' : 'Edit Template'"
            style="width: 90vw; max-width: 1200px; height: 90vh; max-height: 800px"
            closable
            :draggable="false"
        >
            <CmpTemplateDetail
                v-model:dialogOpen="dialogOpen"
                :dialogData="dialogData"
                :mode="dialogMode"
                @saved="getTemplateListData"
            />
        </Dialog>

        <div
            class="my-2 md:my-3 mx-2 md:mx-5 p-3 md:p-5 bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg"
        >
            <div class="flex flex-col md:flex-row gap-2 md:gap-0">
                <div class="flex flex-col w-full my-auto">
                    <h2 class="title-font font-bold">WhatsApp Templates</h2>
                    <h3 class="title-font">Template List of WhatsApp Business Platforms</h3>
                </div>
                <div class="flex justify-end w-full my-auto mt-2 md:mt-0">
                    <UButton size="xl" class="mr-2" @click="getTemplateListData"
                        ><i class="pi pi-refresh"
                    /></UButton>
                    <UButton size="xl" label="Create Template" @click="openCreateDialog" />
                </div>
            </div>
        </div>

        <div
            class="my-2 md:my-3 mx-2 md:mx-5 p-3 md:p-5 bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg overflow-x-auto"
        >
            <DataTable
                v-model:filters="filters"
                class="p-datatable-sm editable-cells-table"
                :value="templateListData"
                showGridlines
                :loading="loading"
                paginator
                :rows="10"
                paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageSelect"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                :globalFilterFields="['name', 'category', 'language', 'status', 'rejected_reason']"
                filterDisplay="menu"
            >
                <template #header>
                    <div class="flex flex-col sm:flex-row gap-2 justify-between">
                        <div class="flex w-full">
                            <InputText
                                v-model="filters['global'].value"
                                placeholder="Search templates"
                                class="p-inputtext-sm w-full"
                            />
                        </div>
                    </div>
                </template>

                <template #footer>
                    <div class="flex text-sm">Total records: {{ templateListData.length }}</div>
                </template>

                <template #empty>
                    <div class="flex justify-center">No templates available</div>
                </template>

                <Column field="action" header="Actions" class="text-sm">
                    <template #body="slotProps">
                        <div
                            v-if="showViewButton(slotProps.data.id)"
                            class="flex gap-1 justify-center"
                        >
                            <UButton size="sm" @click="openEditDialog(slotProps.data)">
                                <i class="pi pi-pencil" />
                            </UButton>
                            <UButton
                                size="sm"
                                severity="danger"
                                @click="deleteTemplate(slotProps.data.id)"
                            >
                                <i class="pi pi-trash" />
                            </UButton>
                        </div>
                    </template>
                </Column>

                <Column field="name" header="Template Name" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-left">{{ slotProps.data.name }}</div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by name"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
                <Column field="category" header="Category" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">{{ slotProps.data.category ?? '-' }}</div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by category"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
                <Column field="language" header="Language" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">{{ slotProps.data.language ?? '-' }}</div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by language"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
                <Column field="status" header="Status" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">{{ slotProps.data.status ?? '-' }}</div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by status"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
                <Column field="rejected_reason" header="Rejected Reason" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">{{ slotProps.data.rejected_reason ?? '-' }}</div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </CmpLayout>
</template>
