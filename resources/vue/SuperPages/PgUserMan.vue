<script setup lang="ts">
import axios from "axios";
import { ref, onBeforeMount } from "vue";
import { timeGreetings } from "../AppCommon";
import { useApiStore } from "../AppState";
import { useDialog } from "primevue/usedialog";

import CmpToast from "../Components/CmpToast.vue";
import CmpLayout from "../Components/CmpLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";

import DialogUserMan from "../DialogComponents/DialogUserMan.vue";

const api = useApiStore();
const timeGreet = timeGreetings();
const dialog = useDialog();
const toastchild = ref<typeof CmpToast>();

defineProps({
    appName: {
        type: String,
        required: true,
    },
    greetings: {
        type: String,
        required: true,
    },
});

type UserListDataInterface = {
    id: string;
    username: string;
    name: string;
    roles: Array<{
        id: string;
        name: string;
    }>;
    permissions: Array<{
        id: string;
        name: string;
    }>;
    user_permission: Array<string>;
};

const breadCrumb = ref([{ label: "User Role Management - YOVoucher" }]);
const userListData = ref(Array<UserListDataInterface>());
const loading = ref<boolean>(false);
const usernameData = ref<string>("");
const nameData = ref<string>("");

const getUserListData = () => {
    loading.value = true;
    axios
        .post(api.getUserList, {
            username: usernameData.value,
            name: nameData.value,
        })
        .then((response) => {
            userListData.value = response.data;
            loading.value = false;
        })
        .catch((error) => {
            toastchild.value?.toastError(error);
            loading.value = false;
        });
};

const showViewButton = (data: string | null | undefined): boolean => {
    if (data !== "" && data !== null && data !== undefined) {
        return true;
    } else {
        return false;
    }
};

const openEditUserDialog = (data: UserListDataInterface | null) => {
    dialog.open(DialogUserMan, {
        props: {
            header: data === null ? "Create User" : "Edit User",
            modal: true,
        },

        data: {
            typeCreate: data === null ? true : false,
            usermanData: data,
        },

        onClose: () => {
            getUserListData();
        },
    });
};

onBeforeMount(() => {
    getUserListData();
});
</script>

<template>
    <CmpToast ref="toastchild" />
    <CmpLayout :bread-crumb="breadCrumb">
        <div class="my-3 mx-5 p-5 bg-white rounded-lg drop-shadow-lg">
            <h2 class="title-font font-bold">{{ timeGreet + greetings }}</h2>
            <h3 class="title-font">User Role Management</h3>
            <div class="grid grid-flow-row text-sm">
                <div class="flex w-full my-0.5">
                    <div class="flex w-full">
                        <div class="w-28 my-auto text-sm">User Name</div>
                        <div class="flex w-full text-sm">
                            <InputText
                                v-model="usernameData"
                                class="w-72 text-sm"
                                placeholder="Enter username"
                            />
                        </div>
                    </div>
                    <div class="flex w-full">
                        <div class="w-28 my-auto text-sm">Name</div>
                        <div class="flex w-full text-sm">
                            <InputText
                                v-model="nameData"
                                class="w-72 text-sm"
                                placeholder="Enter name"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <button class="btn btn-accent w-20 mt-2.5" @click="getUserListData">
                <span class="m-1">Find</span>
            </button>
            <button
                class="btn w-20 mt-2.5 mx-2"
                @click="openEditUserDialog(null)"
            >
                <span class="m-1">Create</span>
            </button>
        </div>
        <div class="my-3 mx-5 p-5 bg-white rounded-lg drop-shadow-lg">
            <DataTable
                class="p-datatable-sm editable-cells-table"
                :value="userListData"
                show-gridlines
                :loading="loading"
                :paginator="true"
                :rows="10"
                paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                :rows-per-page-options="[10, 20, 50, 100]"
            >
                <template #footer>
                    <div class="flex text-sm">
                        Total records: {{ userListData.length }}
                    </div>
                </template>
                <template #empty>
                    <div class="flex justify-center">No data found</div>
                </template>
                <template #loading>
                    <i class="pi pi-spin pi-spinner mr-2.5"></i> Loading data.
                    Please wait.
                </template>
                <Column field="action" header="Actions" class="text-sm">
                    <template #body="slotProps">
                        <div
                            v-if="showViewButton(slotProps.data.id)"
                            class="flex justify-center"
                        >
                            <button
                                class="btn btn-accent"
                                @click="openEditUserDialog(slotProps.data)"
                            >
                                <i class="pi pi-angle-double-right"></i>
                            </button>
                        </div>
                    </template>
                </Column>
                <Column field="username" header="User Name" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">
                            {{ slotProps.data.username }}
                        </div>
                    </template>
                </Column>
                <Column field="name" header="Name" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">{{ slotProps.data.name }}</div>
                    </template>
                </Column>
                <Column field="user_roles" header="Roles" class="text-sm">
                    <template #body="slotProps">
                        <div
                            v-for="role in slotProps.data.roles"
                            :key="role.id"
                            class="text-center"
                        >
                            {{ role.name }}
                        </div>
                    </template>
                </Column>
                <Column
                    field="user_permission"
                    header="Permission"
                    class="text-sm"
                >
                    <template #body="slotProps">
                        <div class="text-center">
                            {{ slotProps.data.user_permission.join(", ") }}
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </CmpLayout>
</template>

<style lang="scss" scoped>
:deep(.p-column-header-content) {
    @apply justify-center;
}
</style>
