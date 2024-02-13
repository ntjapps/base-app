<script setup lang="ts">
import axios from "axios";
import { ref, onBeforeMount } from "vue";
import { timeGreetings, UserDataInterface } from "../AppCommon";
import { useApiStore, useMainStore } from "../AppState";
import { useDialog } from "primevue/usedialog";

import CmpToast from "../Components/CmpToast.vue";
import CmpLayout from "../Components/CmpLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Breadcrumb from "primevue/breadcrumb";
import { FilterMatchMode } from "primevue/api";

import DialogUserMan from "../DialogComponents/DialogUserMan.vue";

const api = useApiStore();
const main = useMainStore();
const timeGreet = timeGreetings();
const dialog = useDialog();
const toastchild = ref<typeof CmpToast>();

const props = defineProps({
    appName: {
        type: String,
        required: true,
    },
    greetings: {
        type: String,
        required: true,
    },
    expandedKeysProps: {
        type: String,
        default: "",
    },
});

const userListData = ref(Array<UserDataInterface>());
const loading = ref<boolean>(false);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    username: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    user_permission: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const getUserListData = () => {
    loading.value = true;
    axios
        .post(api.getUserList)
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

const openEditUserDialog = (data: UserDataInterface | null) => {
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

/** Breadcrumb */
const home = ref({
    icon: "pi pi-home",
});
const items = ref([{ label: "Administration" }, { label: "User Management" }]);

onBeforeMount(() => {
    getUserListData();
    main.updateExpandedKeysMenu(props.expandedKeysProps);
});
</script>

<template>
    <CmpLayout>
        <CmpToast ref="toastchild" />
        <Breadcrumb :home="home" :model="items" />
        <div class="my-3 mx-5 p-5 bg-neutral rounded-lg drop-shadow-lg">
            <div class="flex flex-row">
                <div class="flex flex-col w-full my-auto">
                    <h2 class="title-font font-bold">
                        {{ timeGreet + greetings }}
                    </h2>
                    <h3 class="title-font">User Role Management</h3>
                </div>
                <div class="flex flex-row-reverse w-full my-auto">
                    <button
                        class="btn btn-primary w-20"
                        @click="openEditUserDialog(null)"
                    >
                        <span class="m-1">Create</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="my-3 mx-5 p-5 bg-neutral rounded-lg drop-shadow-lg">
            <DataTable
                v-model:filters="filters"
                class="p-datatable-sm editable-cells-table"
                :value="userListData"
                show-gridlines
                :loading="loading"
                :paginator="true"
                :rows="10"
                paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                :rows-per-page-options="[10, 20, 50, 100]"
                :global-filter-fields="['username', 'name']"
                filter-display="menu"
            >
                <template #header>
                    <div class="flex justify-between">
                        <div class="flex w-full">
                            <InputText
                                v-model="filters['global'].value"
                                placeholder="Search by username or name"
                                class="p-inputtext-sm w-full"
                            />
                        </div>
                    </div>
                </template>
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
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText
                            v-model="filterModel.value"
                            class="w-full"
                            placeholder="Search by username"
                            @input="filterCallback()"
                        />
                    </template>
                </Column>
                <Column field="name" header="Name" class="text-sm">
                    <template #body="slotProps">
                        <div class="text-center">{{ slotProps.data.name }}</div>
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
