<script setup lang="ts">
import axios from 'axios';

import { ref, inject, computed, onMounted } from 'vue';
import { useApiStore } from '../AppState';
import { RoleDataInterface, PermissionDataInterface } from '../AppCommon';

import CmpToast from '../Components/CmpToast.vue';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import { FilterMatchMode } from '@primevue/core/api';

const api = useApiStore();
const toastchild = ref<typeof CmpToast>();

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const dialogRef = inject('dialogRef') as any;

const usermanData = dialogRef.value.data?.usermanData;
const typeCreate = ref<boolean>(dialogRef.value.data?.typeCreate);
const nameData = ref<string>(usermanData?.name);
const usernameData = ref<string>(usermanData?.username);
const roleListData = ref<Array<string>>();
const selectedRoleListData = ref<Array<RoleDataInterface>>();
const permListData = ref<Array<string>>();
const selectedPermListData = ref<Array<PermissionDataInterface>>();

const filters_role = ref({
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const filters_perm = ref({
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const showDeleted = computed(() => {
    return !typeCreate.value;
});

const getUserRoleListData = () => {
    axios
        .post(api.getUserRolePerm)
        .then((response) => {
            roleListData.value = response.data.roles;
            permListData.value = response.data.permissions;
            selectedRoleListData.value = response.data.roles.filter((role: RoleDataInterface) => {
                return (
                    usermanData?.roles?.findIndex((userRole: RoleDataInterface) => {
                        return userRole.id === role.id;
                    }) !== -1
                );
            });
            selectedPermListData.value = response.data.permissions.filter(
                (perm: PermissionDataInterface) => {
                    return (
                        usermanData?.permissions?.findIndex((userPerm: PermissionDataInterface) => {
                            return userPerm.id === perm.id;
                        }) !== -1
                    );
                },
            );
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: 'error',
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
};

const postUserManData = () => {
    axios
        .post(api.postUserManSubmit, {
            type_create: typeCreate.value ? 1 : 0,
            id: usermanData?.id,
            name: nameData.value,
            username: usernameData.value,
            roles: selectedRoleListData.value?.map((role: RoleDataInterface) => {
                return role.id;
            }),
            permissions: selectedPermListData.value?.map((perm: PermissionDataInterface) => {
                return perm.id;
            }),
        })
        .then((response) => {
            dialogRef.value.close();
            toastchild.value?.toastDisplay({
                severity: 'success',
                summary: response.data.title,
                detail: response.data.message,
            });
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: 'error',
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
};

const postDeleteUserManData = () => {
    axios
        .post(api.postDeleteUserManSubmit, {
            id: usermanData?.id,
        })
        .then((response) => {
            dialogRef.value.close();
            toastchild.value?.toastDisplay({
                severity: 'success',
                summary: response.data.title,
                detail: response.data.message,
            });
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: 'error',
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
};

const postResetPasswordUserMandata = () => {
    axios
        .post(api.postResetPasswordUserManSubmit, {
            id: usermanData?.id,
        })
        .then((response) => {
            dialogRef.value.close();
            toastchild.value?.toastDisplay({
                severity: 'success',
                summary: response.data.title,
                detail: response.data.message,
            });
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: 'error',
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
};

onMounted(() => {
    getUserRoleListData();
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
    <div class="flex w-full mt-1">
        <div class="w-28 my-auto text-sm">
            <span>Username:<span class="text-red-500 font-bold">*</span></span>
        </div>
        <div class="flex w-full text-sm">
            <InputText v-model="usernameData" class="w-full text-sm" />
        </div>
    </div>
    <div class="flex w-full justify-evenly mt-2.5">
        <div class="mx-2.5">
            <DataTable
                v-model:filters="filters_role"
                v-model:selection="selectedRoleListData"
                class="p-datatable-sm"
                :value="roleListData"
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
                <Column field="name" header="Direct Roles">
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by role"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>
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
                <Column field="name" header="Direct Permissions">
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
    </div>
    <div class="flex w-full mt-2.5 justify-center">
        <Button
            v-if="showDeleted"
            severity="danger"
            label="Delete"
            @click="postDeleteUserManData()"
        />
        <Button severity="warning" label="Reset Password" @click="postResetPasswordUserMandata()" />
        <Button label="Submit" @click="postUserManData()" />
    </div>
</template>
