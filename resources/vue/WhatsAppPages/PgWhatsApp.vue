<script setup lang="ts">
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { useApiStore, useMainStore } from '../AppState';

import CmpToast from '../Components/CmpToast.vue';
import CmpLayout from '../Components/CmpLayout.vue';
import CmpMessageDetail from './CmpMessageDetail.vue';

import Dialog from '../volt/Dialog.vue';
import DataTable from '../volt/DataTable.vue';
import Column from 'primevue/column';
import InputText from '../volt/InputText.vue';
import { FilterMatchMode } from '@primevue/core/api';

const props = defineProps<{
    appName: string;
    greetings: string;
    expandedKeysProps: string;
}>();

const api = useApiStore();
const main = useMainStore();
const toastchild = ref<typeof CmpToast>();

interface WaThread {
    id: string;
    phone_number: string;
    last_message_at: string | null;
    message_preview: string | null;
}

const threadListData = ref<WaThread[]>([]);
const loading = ref<boolean>(false);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    phone_number: { value: null, matchMode: FilterMatchMode.CONTAINS },
    last_message_at: { value: null, matchMode: FilterMatchMode.CONTAINS },
    message_preview: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const getThreadListData = () => {
    loading.value = true;
    axios
        .post(api.getWaThreadsList)
        .then((response) => {
            threadListData.value = response.data;
            loading.value = false;
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: 'error',
                summary: error.response?.data?.title || 'Error',
                detail: error.response?.data?.message || error.message,
                response: error,
            });
            loading.value = false;
        });
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
    main.updateExpandedKeysMenu(props.expandedKeysProps);
});
</script>

<template>
    <CmpLayout>
        <CmpToast ref="toastchild" />
        <Dialog v-model:visible="dialogOpen" modal header="Message Detail">
            <CmpMessageDetail
                v-model:dialogOpen="dialogOpen"
                :dialogData="dialogData"
                @closeDialog="getThreadListData()"
            />
        </Dialog>
        <div
            class="my-2 md:my-3 mx-2 md:mx-5 p-3 md:p-5 bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg"
        >
            <div class="flex flex-col md:flex-row gap-2 md:gap-0">
                <div class="flex flex-col w-full my-auto">
                    <h2 class="title-font font-bold">WhatsApp Message Threads</h2>
                    <h3 class="title-font">Thread List</h3>
                </div>
            </div>
        </div>
        <div
            class="my-2 md:my-3 mx-2 md:mx-5 p-3 md:p-5 bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg overflow-x-auto"
        >
            <DataTable
                v-model:filters="filters"
                class="p-datatable-sm editable-cells-table"
                :value="threadListData"
                showGridlines
                :loading="loading"
                paginator
                :rows="10"
                paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageSelect"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                :globalFilterFields="['phone_number', 'message_preview']"
                filterDisplay="menu"
            >
                <template #header>
                    <div class="flex flex-col sm:flex-row gap-2 justify-between">
                        <div class="flex w-full">
                            <InputText
                                v-model="filters['global'].value"
                                placeholder="Search by phone number"
                                class="p-inputtext-sm w-full"
                            />
                        </div>
                    </div>
                </template>
                <template #footer>
                    <div class="flex text-sm">Total records: {{ threadListData.length }}</div>
                </template>
                <template #empty>
                    <div class="flex justify-center">No data found</div>
                </template>
                <template #loading>
                    <i class="pi pi-spin pi-spinner mr-2.5"></i> Loading data. Please wait.
                </template>
                <Column field="action" header="Actions" class="text-sm">
                    <template #body="slotProps">
                        <div v-if="showViewButton(slotProps.data.id)" class="flex justify-center">
                            <UButton size="xl" @click="openMessageDialog(slotProps.data)"
                                ><i class="pi pi-angle-double-right"
                            /></UButton>
                        </div>
                    </template>
                </Column>
                <Column field="phone_number" header="Phone Number" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">
                            {{ slotProps.data.phone_number }}
                        </div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by phone number"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
                <Column field="last_message_at" header="Last Message At" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">
                            {{ slotProps.data.last_message_at }}
                        </div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by date/time"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
                <Column field="message_preview" header="Preview" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-left">
                            {{ slotProps.data.message_preview ?? '-' }}
                        </div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by preview"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
    </CmpLayout>
</template>
