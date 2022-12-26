<script setup lang="ts">
import { ref } from "vue";
import { timeGreetings } from "../AppCommon";
import { useApiStore } from "../AppState";

import axios from "axios";
import { useError } from "../AppAxiosResp";

import CmpLayout from "../Components/CmpLayout.vue";
import ButtonVue from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";

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

const timeGreet = timeGreetings();
const api = useApiStore();

type BreadCrumbType = Array<{ label: string }>;
type UserListDataType = Array<{
    id: number;
    username: string;
    name: string;
    email: string;
    role: string;
    created_at: string;
    updated_at: string;
}>;

const breadCrumb = ref<BreadCrumbType>([{ label: "User Role Management" }]);
const userListData = ref<Array<UserListDataType>>(Array<UserListDataType>());
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
            useError(error);
            loading.value = false;
        });
};

const showViewButton = (data: string): boolean => {
    if (data !== "" && data !== null && data !== undefined) {
        return true;
    } else {
        return false;
    }
};
</script>

<template>
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
                            />
                        </div>
                    </div>
                    <div class="flex w-full">
                        <div class="w-28 my-auto text-sm">Name</div>
                        <div class="flex w-full text-sm">
                            <InputText
                                v-model="nameData"
                                class="w-72 text-sm"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <ButtonVue
                class="p-button-success p-button-sm w-20 mt-2.5"
                label="Find"
                @click="getUserListData"
            />
        </div>
        <div class="my-3 mx-5 p-5 bg-white rounded-lg drop-shadow-lg">
            <DataTable
                class="p-datatable-sm editable-cells-table"
                :value="userListData"
                show-gridlines
                :loading="loading"
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
                        <div class="flex justify-center">
                            <div
                                v-if="showViewButton(slotProps.data.id)"
                                class="mx-1"
                            >
                                <ButtonVue
                                    class="p-button-success p-button-sm"
                                    icon="pi pi-search"
                                    label="View"
                                />
                            </div>
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
