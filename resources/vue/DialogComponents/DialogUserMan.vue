<script setup lang="ts">
import axios from "axios";

import { ref, inject, computed, onMounted } from "vue";
import { useApiStore } from "../AppState";
import { RoleDataInterface, PermissionDataInterface } from "../AppCommon";

import CmpToast from "../Components/CmpToast.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";

const api = useApiStore();
const toastchild = ref<typeof CmpToast>();

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const errorMessageData = ref<Array<any>>([]);

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const dialogRef = inject("dialogRef") as any;

const usermanData = dialogRef.value.data?.usermanData;
const typeCreate = ref<boolean>(dialogRef.value.data?.typeCreate);
const nameData = ref<string>(usermanData?.name);
const usernameData = ref<string>(usermanData?.username);
const roleListData = ref<Array<string>>();
const selectedRoleListData = ref<Array<RoleDataInterface>>();
const permListData = ref<Array<string>>();
const selectedPermListData = ref<Array<PermissionDataInterface>>();

const showDeleted = computed(() => {
    return !typeCreate.value;
});

const getUserRoleListData = () => {
    axios
        .post(api.getUserRolePerm)
        .then((response) => {
            roleListData.value = response.data.roles;
            permListData.value = response.data.permissions;
            selectedRoleListData.value = response.data.roles.filter(
                (role: RoleDataInterface) => {
                    return (
                        usermanData?.roles?.findIndex(
                            (userRole: RoleDataInterface) => {
                                return userRole.name === role.name;
                            },
                        ) !== -1
                    );
                },
            );
            selectedPermListData.value = response.data.permissions.filter(
                (perm: PermissionDataInterface) => {
                    return (
                        usermanData?.permissions?.findIndex(
                            (userPerm: PermissionDataInterface) => {
                                return userPerm.name === perm.name;
                            },
                        ) !== -1
                    );
                },
            );
        })
        .catch((error) => {
            errorMessageData.value = error.response.data.errors;
        });
};

const postUserManData = () => {
    axios
        .post(api.postUserManSubmit, {
            type_create: typeCreate.value ? 1 : 0,
            id: usermanData?.id,
            name: nameData.value,
            username: usernameData.value,
            roles: selectedRoleListData.value?.map(
                (role: RoleDataInterface) => {
                    return role.name;
                },
            ),
            permissions: selectedPermListData.value?.map(
                (perm: PermissionDataInterface) => {
                    return perm.name;
                },
            ),
        })
        .then((response) => {
            dialogRef.value.close();
            toastchild.value?.toastSuccess(response.data.message);
        })
        .catch((error) => {
            errorMessageData.value = error.response.data.errors;
        });
};

const postDeleteUserManData = () => {
    axios
        .post(api.postDeleteUserManSubmit, {
            id: usermanData?.id,
        })
        .then((response) => {
            dialogRef.value.close();
            toastchild.value?.toastSuccess(response.data.message);
        })
        .catch((error) => {
            errorMessageData.value = error.response.data.errors;
        });
};

const postResetPasswordUserMandata = () => {
    axios
        .post(api.postResetPasswordUserManSubmit, {
            id: usermanData?.id,
        })
        .then((response) => {
            dialogRef.value.close();
            toastchild.value?.toastSuccess(response.data.message);
        })
        .catch((error) => {
            errorMessageData.value = error.response.data.errors;
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
                v-model:selection="selectedRoleListData"
                class="p-datatable-sm"
                :value="roleListData"
                show-gridlines
                :paginator="true"
                :rows="10"
                paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                :rows-per-page-options="[10, 20, 50, 100]"
            >
                <template #empty>
                    <div class="flex justify-center">No data found</div>
                </template>
                <template #loading>
                    <i class="pi pi-spin pi-spinner mr-2.5"></i>
                    Processing data. Please wait.
                </template>
                <Column selection-mode="multiple"></Column>
                <Column field="name" header="Direct Roles" />
            </DataTable>
        </div>
        <div class="mx-2.5">
            <DataTable
                v-model:selection="selectedPermListData"
                class="p-datatable-sm"
                :value="permListData"
                show-gridlines
                :paginator="true"
                :rows="10"
                paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                :rows-per-page-options="[10, 20, 50, 100]"
            >
                <template #empty>
                    <div class="flex justify-center">No data found</div>
                </template>
                <template #loading>
                    <i class="pi pi-spin pi-spinner mr-2.5"></i>
                    Processing data. Please wait.
                </template>
                <Column selection-mode="multiple"></Column>
                <Column field="name" header="Direct Permissions" />
            </DataTable>
        </div>
    </div>
    <div class="flex w-full mt-1">
        <div
            v-for="(messages, index) in errorMessageData"
            :key="index"
            class="w-full text-center text-sm italic font-bold text-red-500"
        >
            {{ messages[0] }}
        </div>
    </div>
    <div class="flex w-full mt-2.5 justify-center">
        <button
            v-if="showDeleted"
            class="btn btn-error w-24 mx-2 text-sm"
            @click="postDeleteUserManData()"
        >
            <span class="m-1">Delete</span>
        </button>
        <button
            v-if="showDeleted"
            class="btn btn-warning w-24 mx-2 text-sm"
            @click="postResetPasswordUserMandata()"
        >
            <span class="m-1">Reset Password</span>
        </button>
        <button class="btn w-24 mx-2 text-sm" @click="postUserManData">
            <span class="m-1">Submit</span>
        </button>
    </div>
</template>
