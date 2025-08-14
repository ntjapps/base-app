<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { api } from '../AppAxios';
import { PermissionDataInterface, RoleListDataInterface } from '../AppCommon';
import CmpToast from '../Components/CmpToast.vue';

import DataTable from '../volt/DataTable.vue';
import Column from 'primevue/column';
import InputText from '../volt/InputText.vue';
import { FilterMatchMode } from '@primevue/core/api';

const props = defineProps<{
    dialogOpen: boolean;
    dialogData: RoleListDataInterface | null;
    dialogTypeCreate: boolean;
}>();
const emit = defineEmits<{
    (e: 'closeDialog'): void;
    (e: 'update:dialogOpen', value: boolean): void;
}>();
const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);
watch(
    () => props.dialogOpen,
    (newValue) => {
        if (!newValue) {
            emit('closeDialog');
        }
        emit('update:dialogOpen', newValue);
    },
);
const closeDialogFunction = () => {
    emit('closeDialog');
    emit('update:dialogOpen', false);
};

const rolemanData = props.dialogData;
const typeCreate = ref<boolean>(props.dialogTypeCreate);
const nameData = ref<string>(rolemanData?.name ?? '');
const permListData = ref<Array<string>>();
const selectedPermListData = ref<Array<PermissionDataInterface>>();

const filters_perm = ref({
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const showDeleted = computed(() => {
    return !typeCreate.value;
});

const getRoleListData = async () => {
    try {
    const response = await api.getUserRolePerm();
        permListData.value = response.data.permissions;
        selectedPermListData.value = response.data.permissions.filter(
            (perm: PermissionDataInterface) => {
                return (
                    rolemanData?.permissions?.findIndex((userPerm: PermissionDataInterface) => {
                        return userPerm.name === perm.name;
                    }) !== -1
                );
            },
        );
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    }
};

const postRolemanData = async () => {
    try {
    const response = await api.postRoleSubmit({
            type_create: typeCreate.value ? 1 : 0,
            role_name: typeCreate.value ? nameData.value : null,
            role_id: typeCreate.value ? null : rolemanData?.id,
            role_rename: typeCreate.value ? null : nameData.value,
            permissions: selectedPermListData.value?.map((perm: PermissionDataInterface) => {
                return perm.id;
            }),
        });
        closeDialogFunction();
        toastchild.value?.toastDisplay({
            severity: 'success',
            summary: response.data.title,
            detail: response.data.message,
        });
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    }
};

const postDeleteRolemanData = async () => {
    try {
    const response = await api.postDeleteRoleSubmit({
            id: rolemanData?.id,
        });
        closeDialogFunction();
        toastchild.value?.toastDisplay({
            severity: 'success',
            summary: response.data.title,
            detail: response.data.message,
        });
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    }
};

onMounted(() => {
    getRoleListData();
});
</script>

<template>
    <CmpToast ref="toastchild" />
    <div class="flex w-full mt-1">
        <div class="w-28 my-auto text-sm">
            <span>Name:<span class="text-red-500 font-bold">*</span></span>
        </div>
        <div class="flex w-full text-sm">
            <InputText v-model="nameData" class="w-full text-sm" />
        </div>
    </div>
    <div class="flex w-full justify-evenly mt-2.5">
        <div class="mx-2.5">
            <DataTable
                v-model:filters="filters_perm"
                v-model:selection="selectedPermListData"
                class="p-datatable-sm"
                :value="permListData"
                showGridlines
                paginator
                :rows="10"
                paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageSelect"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                filterDisplay="menu"
            >
                <template #empty>
                    <div class="flex justify-center">No data found</div>
                </template>
                <template #loading>
                    <i class="pi pi-spin pi-spinner mr-2.5"></i>
                    Processing data. Please wait.
                </template>
                <Column selectionMode="multiple"></Column>
                <Column field="ability.title" header="Direct Permissions">
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by permission"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
                <Column field="ability_type" header="Type">
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by type"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
    <div class="flex w-full mt-2 justify-center flex-wrap gap-2">
        <UButton
            v-if="showDeleted"
            size="xl"
            color="error"
            label="Delete"
            class="m-1 md:m-2"
            @click="postDeleteRolemanData()"
        />
        <UButton size="xl" class="m-1 md:m-2" label="Submit" @click="postRolemanData" />
    </div>
</template>
