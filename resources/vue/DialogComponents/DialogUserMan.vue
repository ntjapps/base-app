<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { api } from '../AppAxios';
import { RoleDataInterface, PermissionDataInterface, UserDataInterface } from '../AppCommon';
import CmpCustomTable from '../Components/CmpCustomTable.vue';
import StdButton from '../Components/StdButton.vue';
import { useTableSort } from '../composables/useTableSort';

const props = defineProps<{
    dialogOpen: boolean;
    dialogData: UserDataInterface | null;
    dialogTypeCreate: boolean;
}>();

const emit = defineEmits<{
    (e: 'closeDialog'): void;
    (e: 'update:dialogOpen', value: boolean): void;
}>();

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

const usermanData = props.dialogData;
const typeCreate = ref<boolean>(props.dialogTypeCreate);
const nameData = ref<string>(usermanData?.name ?? '');
const usernameData = ref<string>(usermanData?.username ?? '');
const roleListData = ref<Array<RoleDataInterface>>([]);
const selectedRoleListData = ref<Array<RoleDataInterface>>([]);
const permListData = ref<Array<PermissionDataInterface>>([]);
const selectedPermListData = ref<Array<PermissionDataInterface>>([]);

// Table logic
const searchRole = ref('');
const pageRole = ref(1);
const pageCountRole = 10;
const searchPerm = ref('');
const pagePerm = ref(1);
const pageCountPerm = 10;

const columnsRole = [
    { id: 'name', key: 'name', label: 'Direct Roles', sortable: true },
    { id: 'role_types', key: 'role_types', label: 'Type', sortable: true },
];

const columnsPerm = [{ id: 'perm_name', key: 'name', label: 'Direct Permissions', sortable: true }];

const filteredRoleList = computed(() => {
    if (!roleListData.value) return [];
    if (!searchRole.value) return roleListData.value;
    return roleListData.value.filter(
        (item) =>
            String(item.name).toLowerCase().includes(searchRole.value.toLowerCase()) ||
            String(item.role_types).toLowerCase().includes(searchRole.value.toLowerCase()),
    );
});

// Use the sorting composables for both tables
const { sortBy: sortByRole, sortedData: sortedRoleData } = useTableSort(filteredRoleList);

const filteredPermList = computed(() => {
    if (!permListData.value) return [];
    if (!searchPerm.value) return permListData.value;
    return permListData.value.filter((item) =>
        String(item.name).toLowerCase().includes(searchPerm.value.toLowerCase()),
    );
});

const { sortBy: sortByPerm, sortedData: sortedPermData } = useTableSort(filteredPermList);

const showDeleted = computed(() => {
    return !typeCreate.value;
});

const getUserRoleListData = async () => {
    try {
        const response = await api.getUserRolePerm();
        const data = response.data as unknown as {
            roles: Array<RoleDataInterface>;
            permissions: Array<PermissionDataInterface>;
        };
        if (!data.roles || !data.permissions) {
            throw new Error('Invalid response structure');
        }
        roleListData.value = data.roles;
        permListData.value = data.permissions;
        selectedRoleListData.value = data.roles.filter((role: RoleDataInterface) => {
            return (
                usermanData?.roles?.findIndex((userRole: RoleDataInterface) => {
                    return userRole.id === role.id;
                }) !== -1
            );
        });
        const permissions = (
            response.data as unknown as { permissions: Array<PermissionDataInterface> }
        ).permissions;
        selectedPermListData.value = permissions.filter((perm: PermissionDataInterface) => {
            return (
                usermanData?.permissions?.findIndex((userPerm: PermissionDataInterface) => {
                    return userPerm.id === perm.id;
                }) !== -1
            );
        });
    } catch {
        // Toast handled globally by ApiClient
    }
};

const postUserManData = async () => {
    try {
        const response = await api.postUserManSubmit({
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
        });

        // Handle 202 Accepted or success
        api.handle202Accepted(response, 'User task queued for processing');
        closeDialogFunction();
    } catch {
        // Toast handled globally by ApiClient
    }
};

const postDeleteUserManData = async () => {
    try {
        const id = usermanData?.id as string | number;
        const response = await api.postDeleteUserManSubmit(id);

        // Handle 202 Accepted or success
        api.handle202Accepted(response, 'User task queued for processing');
        closeDialogFunction();
    } catch {
        // Toast handled globally by ApiClient
    }
};

const postResetPasswordUserMandata = async () => {
    try {
        const response = await api.postResetPasswordUserManSubmit({
            id: usermanData?.id,
        });

        // Handle 202 Accepted or success
        api.handle202Accepted(response, 'Password reset task queued for processing');
        closeDialogFunction();
    } catch {
        // Toast handled globally by ApiClient
    }
};

onMounted(() => {
    getUserRoleListData();
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <!-- min-w for label alignment on mobile -->
                <span>Name:<span class="text-red-500 font-bold">*</span></span>
            </div>
            <div class="w-full text-sm">
                <UInput v-model="nameData" class="w-full text-sm" />
            </div>
        </div>
        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Username:<span class="text-red-500 font-bold">*</span></span>
            </div>
            <div class="w-full text-sm">
                <UInput v-model="usernameData" class="w-full text-sm" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="w-full min-w-[250px] rounded-xl border border-gray-200 bg-white p-3">
                <div class="mb-2 text-sm font-semibold text-gray-700">Direct Roles</div>
                <div class="mb-3">
                    <UInput v-model="searchRole" placeholder="Search roles..." class="w-full" />
                </div>
                <CmpCustomTable
                    v-model="selectedRoleListData"
                    v-model:sortBy="sortByRole"
                    v-model:page="pageRole"
                    :rows="sortedRoleData"
                    :columns="columnsRole"
                    :itemsPerPage="pageCountRole"
                    class="w-full"
                />
            </div>

            <div class="w-full min-w-[250px] rounded-xl border border-gray-200 bg-white p-3">
                <div class="mb-2 text-sm font-semibold text-gray-700">Direct Permissions</div>
                <div class="mb-3">
                    <UInput
                        v-model="searchPerm"
                        placeholder="Search permissions..."
                        class="w-full"
                    />
                </div>
                <CmpCustomTable
                    v-model="selectedPermListData"
                    v-model:sortBy="sortByPerm"
                    v-model:page="pagePerm"
                    :rows="sortedPermData"
                    :columns="columnsPerm"
                    :itemsPerPage="pageCountPerm"
                    class="w-full"
                />
            </div>
        </div>

        <div class="flex w-full flex-wrap justify-end gap-2 border-t border-gray-200 pt-3">
            <StdButton v-if="showDeleted" variant="danger" @click="postDeleteUserManData()">
                <span>Delete</span>
            </StdButton>
            <StdButton variant="warn" @click="postResetPasswordUserMandata()">
                <span>Reset Password</span>
            </StdButton>
            <StdButton variant="primary" label="Submit" @click="postUserManData()" />
        </div>
    </div>
</template>
