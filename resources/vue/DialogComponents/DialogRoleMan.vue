<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { api } from '../AppAxios';
import { PermissionDataInterface, RoleListDataInterface } from '../AppCommon';
import CmpCustomTable from '../Components/CmpCustomTable.vue';
import StdButton from '../Components/StdButton.vue';
import { useTableSort } from '../composables/useTableSort';

const props = defineProps<{
    dialogOpen: boolean;
    dialogData: RoleListDataInterface | null;
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

const rolemanData = props.dialogData;
const typeCreate = ref<boolean>(props.dialogTypeCreate);
const nameData = ref<string>(rolemanData?.name ?? '');
const permListData = ref<Array<PermissionDataInterface>>([]);
const selectedPermListData = ref<Array<PermissionDataInterface>>([]);

// Table logic
const search = ref('');
const page = ref(1);
const pageCount = 10;
const columns = [{ id: 'name', key: 'name', label: 'Direct Permissions' }];

const filteredRows = computed(() => {
    let confirm = permListData.value || [];
    if (search.value) {
        confirm = confirm.filter((perm) => {
            return Object.values(perm).some((value) => {
                return String(value).toLowerCase().includes(search.value.toLowerCase());
            });
        });
    }
    return confirm;
});

// Use the sorting composable
const { sortBy, sortedData } = useTableSort(filteredRows);

const showDeleted = computed(() => {
    return !typeCreate.value;
});

const getRoleListData = async () => {
    try {
        const response = (await api.getUserRolePerm()) as unknown as {
            data: { permissions: PermissionDataInterface[] };
        };
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
    } catch {
        // Toast handled globally by ApiClient
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

        // Handle 202 Accepted or success
        api.handle202Accepted(response, 'Role task queued for processing');
        closeDialogFunction();
    } catch {
        // Toast handled globally by ApiClient
    }
};

const postDeleteRolemanData = async () => {
    try {
        const response = await api.postDeleteRoleSubmit(rolemanData?.id as string | number);

        // Handle 202 Accepted or success
        api.handle202Accepted(response, 'Role deletion task queued for processing');
        closeDialogFunction();
    } catch {
        // Toast handled globally by ApiClient
    }
};

onMounted(() => {
    getRoleListData();
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Name:<span class="text-red-500 font-bold">*</span></span>
            </div>
            <div class="w-full text-sm">
                <UInput v-model="nameData" class="w-full text-sm" />
            </div>
        </div>

        <div class="w-full rounded-xl border border-gray-200 bg-white p-3">
            <div class="mb-2 text-sm font-semibold text-gray-700">Direct Permissions</div>
            <div class="mb-3">
                <UInput v-model="search" placeholder="Search permissions..." class="w-full" />
            </div>
            <CmpCustomTable
                v-model="selectedPermListData"
                v-model:sortBy="sortBy"
                v-model:page="page"
                :rows="sortedData"
                :columns="columns"
                :itemsPerPage="pageCount"
                class="w-full"
            />
        </div>

        <div class="flex w-full flex-wrap justify-end gap-2 border-t border-gray-200 pt-3">
            <StdButton
                v-if="showDeleted"
                variant="danger"
                label="Delete"
                @click="postDeleteRolemanData()"
            />
            <StdButton variant="primary" label="Submit" @click="postRolemanData" />
        </div>
    </div>
</template>
