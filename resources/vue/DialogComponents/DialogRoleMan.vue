<script setup lang="ts">
import axios from "axios";

import { ref, inject, computed, onMounted } from "vue";
import { useApiStore } from "../AppState";
import { PermissionDataInterface } from "../AppCommon";

import CmpToast from "../Components/CmpToast.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import { FilterMatchMode } from "@primevue/core/api";

const api = useApiStore();
const toastchild = ref<typeof CmpToast>();

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const dialogRef = inject("dialogRef") as any;

const rolemanData = dialogRef.value.data?.rolemanData;
const typeCreate = ref<boolean>(dialogRef.value.data?.typeCreate);
const nameData = ref<string>(rolemanData?.name);
const permListData = ref<Array<string>>();
const selectedPermListData = ref<Array<PermissionDataInterface>>();

const filters_perm = ref({
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const showDeleted = computed(() => {
    return !typeCreate.value;
});

const getRoleListData = () => {
    axios
        .post(api.getUserRolePerm)
        .then((response) => {
            permListData.value = response.data.permissions;
            selectedPermListData.value = response.data.permissions.filter(
                (perm: PermissionDataInterface) => {
                    return (
                        rolemanData?.permissions?.findIndex(
                            (userPerm: PermissionDataInterface) => {
                                return userPerm.name === perm.name;
                            },
                        ) !== -1
                    );
                },
            );
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: "error",
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
};

const postRolemanData = () => {
    axios
        .post(api.postRoleSubmit, {
            type_create: typeCreate.value ? 1 : 0,
            role_name: typeCreate.value ? nameData.value : null,
            role_id: typeCreate.value ? null : rolemanData?.id,
            role_rename: typeCreate.value ? null : nameData.value,
            permissions: selectedPermListData.value?.map(
                (perm: PermissionDataInterface) => {
                    return perm.name;
                },
            ),
        })
        .then((response) => {
            dialogRef.value.close();
            toastchild.value?.toastDisplay({
                severity: "success",
                summary: response.data.title,
                detail: response.data.message,
            });
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: "error",
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
};

const postDeleteRolemanData = () => {
    axios
        .post(api.postDeleteRoleSubmit, {
            id: rolemanData?.id,
        })
        .then((response) => {
            dialogRef.value.close();
            toastchild.value?.toastDisplay({
                severity: "success",
                summary: response.data.title,
                detail: response.data.message,
            });
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: "error",
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
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
                show-gridlines
                :paginator="true"
                :rows="10"
                paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageSelect"
                :rows-per-page-options="[10, 20, 50, 100]"
                filter-display="menu"
            >
                <template #empty>
                    <div class="flex justify-center">No data found</div>
                </template>
                <template #loading>
                    <i class="pi pi-spin pi-spinner mr-2.5"></i>
                    Processing data. Please wait.
                </template>
                <Column selection-mode="multiple"></Column>
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
        <button
            v-if="showDeleted"
            class="btn btn-error w-24 mx-2 text-sm"
            @click="postDeleteRolemanData()"
        >
            <span class="m-1">Delete</span>
        </button>
        <button
            class="btn btn-primary w-24 mx-2 text-sm"
            @click="postRolemanData"
        >
            <span class="m-1">Submit</span>
        </button>
    </div>
</template>
