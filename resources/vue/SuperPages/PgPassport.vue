<script setup lang="ts">
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { timeGreetings, ClientListDataInterface, dateView } from '../AppCommon';
import { useApiStore, useMainStore } from '../AppState';

import CmpToast from '../Components/CmpToast.vue';
import CmpLayout from '../Components/CmpLayout.vue';

import Dialog from '../volt/Dialog.vue';
import DataTable from '../volt/DataTable.vue';
import Column from 'primevue/column';
import InputText from '../volt/InputText.vue';
import { FilterMatchMode } from '@primevue/core/api';

import DialogClientMan from '../DialogComponents/DialogClientMan.vue';

const props = defineProps<{
    appName: string;
    greetings: string;
    expandedKeysProps: string;
}>();
const api = useApiStore();
const main = useMainStore();
const timeGreet = timeGreetings();
const toastchild = ref<typeof CmpToast>();

const clientListData = ref(Array<ClientListDataInterface>());
const loading = ref<boolean>(false);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const getClientListData = () => {
    loading.value = true;
    axios
        .post(api.postGetOauthClient)
        .then((response) => {
            clientListData.value = response.data;
            loading.value = false;
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: 'error',
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
            loading.value = false;
        });
};

const checkClientGrant = (data: string | null | undefined): boolean => {
    if (data === '' || data === null || data === undefined) {
        return true;
    } else {
        return false;
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
    main.updateExpandedKeysMenu(props.expandedKeysProps);
});
</script>

<template>
    <CmpLayout>
        <CmpToast ref="toastchild" />
        <Dialog v-model:visible="dialogOpen" modal :header="dialogHeader">
            <DialogClientMan
                v-model:dialogOpen="dialogOpen"
                :dialogData="dialogData"
                :dialogTypeCreate="dialogData === null ? true : false"
                @closeDialog="getClientListData()"
            />
        </Dialog>
        <div class="my-3 mx-5 p-5 bg-surface-200 rounded-lg drop-shadow-lg">
            <div class="flex flex-row">
                <div class="flex flex-col w-full my-auto">
                    <h2 class="title-font font-bold">
                        {{ timeGreet + greetings }}
                    </h2>
                    <h3 class="title-font">Passport Management</h3>
                </div>
                <div class="flex justify-end w-full my-auto">
                    <UButton size="xl" class="m-2" @click="getClientListData">
                        <i class="pi pi-refresh" />
                    </UButton>
                    <UButton
                        size="xl"
                        class="m-2"
                        label="Create Client"
                        @click="openEditClientDialog(null)"
                    />
                </div>
            </div>
        </div>
        <div class="my-3 mx-5 p-5 bg-surface-200 rounded-lg drop-shadow-lg">
            <DataTable
                v-model:filters="filters"
                class="p-datatable-sm editable-cells-table"
                :value="clientListData"
                showGridlines
                :loading="loading"
                paginator
                :rows="10"
                paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageSelect"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                :globalFilterFields="['name']"
                filterDisplay="menu"
            >
                <template #header>
                    <div class="flex justify-between">
                        <div class="flex w-full">
                            <InputText
                                v-model="filters['global'].value"
                                placeholder="Search by client name"
                                class="p-inputtext-sm w-full"
                            />
                        </div>
                    </div>
                </template>
                <template #footer>
                    <div class="flex text-sm">Total records: {{ clientListData.length }}</div>
                </template>
                <template #empty>
                    <div class="flex justify-center">No data found</div>
                </template>
                <template #loading>
                    <i class="pi pi-spin pi-spinner mr-2.5"></i> Loading data. Please wait.
                </template>
                <Column field="action" header="Actions" class="text-sm">
                    <template #body="slotProps">
                        <div v-if="slotProps.data.allowed_action" class="flex justify-center">
                            <UButton size="xl" @click="openEditClientDialog(slotProps.data)">
                                <i class="pi pi-angle-double-right" />
                            </UButton>
                        </div>
                    </template>
                </Column>
                <Column field="name" header="Client Name" class="text-sm">
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by client name"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
                <Column field="redirect" header="Redirect URL" class="text-sm text-left" />
                <Column
                    field="personal_access_client"
                    header="Personal Access Client"
                    class="text-sm"
                >
                    <template #body="slotProps">
                        <div class="text-center">
                            <UButton
                                v-if="slotProps.data.personal_access_client"
                                size="xl"
                                color="success"
                                label="Yes"
                            />
                            <UButton v-else size="xl" color="error" label="No" />
                        </div>
                    </template>
                </Column>
                <Column field="redirect" header="Client Grant" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">
                            <UButton
                                v-if="checkClientGrant(slotProps.data.redirect)"
                                size="xl"
                                color="success"
                                label="Yes"
                            />
                            <UButton v-else size="xl" color="error" label="No" />
                        </div>
                    </template>
                </Column>
                <Column field="revoked" header="Revoked" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">
                            <UButton
                                v-if="slotProps.data.revoked"
                                size="xl"
                                color="error"
                                label="Yes"
                            />
                            <UButton v-else size="xl" color="success" label="No" />
                        </div>
                    </template>
                </Column>
                <Column field="created_at" header="Created At" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">
                            {{ dateView(slotProps.data.created_at) }}
                        </div>
                    </template>
                </Column>
                <Column field="updated_at" header="Updated At" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">
                            {{ dateView(slotProps.data.updated_at) }}
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </CmpLayout>
</template>
