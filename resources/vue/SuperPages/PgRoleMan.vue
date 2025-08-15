<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { timeGreetings, RoleListDataInterface } from '../AppCommon';
import { useMainStore } from '../AppState';
import { api } from '../AppAxios';
import CmpToast from '../Components/CmpToast.vue';
import CmpLayout from '../Components/CmpLayout.vue';

import Dialog from '../volt/Dialog.vue';
import DataTable from '../volt/DataTable.vue';
import Column from 'primevue/column';
import InputText from '../volt/InputText.vue';
import { FilterMatchMode } from '@primevue/core/api';

import DialogRoleMan from '../DialogComponents/DialogRoleMan.vue';

const props = defineProps<{
    appName: string;
    greetings: string;
    expandedKeysProps: string;
}>();
const main = useMainStore();
const timeGreet = timeGreetings();
const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);

const roleListData = ref(Array<RoleListDataInterface>());
const loading = ref<boolean>(false);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    permissions_array: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const getRoleListData = async () => {
    try {
        loading.value = true;
        const response = await api.getRoleList();
        roleListData.value = response.data as unknown as RoleListDataInterface[];
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    } finally {
        loading.value = false;
    }
};

const showViewButton = (data: string | null | undefined): boolean => {
    if (data !== '' && data !== null && data !== undefined) {
        return true;
    } else {
        return false;
    }
};

const dialogOpen = ref<boolean>(false);
const dialogData = ref<RoleListDataInterface | null>(null);
const dialogHeader = ref<string>('Create Role');

const openEditRoleDialog = (data: RoleListDataInterface | null) => {
    dialogOpen.value = true;
    dialogData.value = data;
    if (data === null) {
        dialogHeader.value = 'Create Role';
    } else {
        dialogHeader.value = 'Edit Role';
    }
};

onMounted(() => {
    getRoleListData();
    main.updateExpandedKeysMenu(props.expandedKeysProps);
});
</script>

<template>
    <CmpLayout>
        <CmpToast ref="toastchild" />
        <Dialog v-model:visible="dialogOpen" modal :header="dialogHeader">
            <DialogRoleMan
                v-model:dialogOpen="dialogOpen"
                :dialogData="dialogData"
                :dialogTypeCreate="dialogData === null ? true : false"
                @closeDialog="getRoleListData()"
            />
        </Dialog>
        <div class="my-3 mx-5 p-5 bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg">
            <div class="flex flex-row">
                <div class="flex flex-col w-full my-auto">
                    <h2 class="title-font font-bold">
                        {{ timeGreet + greetings }}
                    </h2>
                    <h3 class="title-font">Role Management</h3>
                </div>
                <div class="flex justify-end w-full my-auto">
                    <UButton size="xl" label="Create Role" @click="openEditRoleDialog(null)" />
                </div>
            </div>
        </div>
        <div class="my-3 mx-5 p-5 bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg">
            <DataTable
                v-model:filters="filters"
                class="p-datatable-sm editable-cells-table"
                :value="roleListData"
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
                                placeholder="Search by role name"
                                class="p-inputtext-sm w-full"
                            />
                        </div>
                    </div>
                </template>
                <template #footer>
                    <div class="flex text-sm">Total records: {{ roleListData.length }}</div>
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
                            <UButton size="xl" @click="openEditRoleDialog(slotProps.data)">
                                <i class="pi pi-angle-double-right" />
                            </UButton>
                        </div>
                    </template>
                </Column>
                <Column field="name" header="Role Name" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">{{ slotProps.data.name }}</div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by role name"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
                <Column field="permissions_array" header="Permission" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">
                            {{ slotProps.data.permissions_array.join(', ') }}
                        </div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by permission"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
    </CmpLayout>
</template>
