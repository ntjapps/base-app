<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { timeGreetings, RoleListDataInterface } from '../AppCommon';
import { useEchoStore } from '../AppState';
import { storeToRefs } from 'pinia';
import { api } from '../AppAxios';
import CmpToast from '../Components/CmpToast.vue';
import CmpLayout from '../Components/CmpLayout.vue';
import CmpCustomTable from '../Components/CmpCustomTable.vue';
import StdButton from '../Components/StdButton.vue';
import { useTableSort } from '../composables/useTableSort';

import DialogRoleMan from '../DialogComponents/DialogRoleMan.vue';
import type { EchoWithMethods } from '../types/echo';

const props = defineProps<{ greetings?: string }>();
const greetings = props.greetings ?? '';

const echo = useEchoStore();
const { laravelEcho } = storeToRefs(echo);
const timeGreet = timeGreetings();
const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);

const roleListData = ref(Array<RoleListDataInterface>());
const loading = ref<boolean>(false);

// Table logic
const q = ref('');
const page = ref(1);
const pageCount = 10;

const columns = [
    { id: 'action', key: 'action', label: 'Actions' },
    { id: 'name', key: 'name', label: 'Role Name', sortable: true },
    { id: 'permissions_array', key: 'permissions_array', label: 'Permission' },
];

const filteredRows = computed(() => {
    if (!q.value) {
        return roleListData.value;
    }
    return roleListData.value.filter((role) => {
        return Object.values(role).some((value) => {
            return String(value).toLowerCase().includes(q.value.toLowerCase());
        });
    });
});

// Use the sorting composable
const { sortBy, sortedData } = useTableSort(filteredRows);

const getRoleListData = async () => {
    try {
        loading.value = true;
        const response = await api.getRoleList();
        // Backend now returns { data: [...] } format
        roleListData.value = (response.data?.data || []) as unknown as RoleListDataInterface[];
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

    // Subscribe to role create/update/delete events and refresh list
    try {
        const echoInstance = laravelEcho.value as unknown as EchoWithMethods | undefined;
        if (echoInstance && typeof echoInstance.private === 'function') {
            const ch = echoInstance.private('roleman.event');
            ch.listen('RoleCreated', () => getRoleListData());
            ch.listen('RoleUpdated', () => getRoleListData());
            ch.listen('RoleDeleted', () => getRoleListData());
        }
    } catch (err) {
        console.debug('Echo private channel not available during mount.', err);
    }
});

onUnmounted(() => {
    try {
        const echoInstance = laravelEcho.value as unknown as EchoWithMethods | undefined;
        if (echoInstance && typeof echoInstance.leave === 'function') {
            echoInstance.leave('roleman.event');
        }
    } catch (err) {
        console.debug('Echo leave failed during unmount.', err);
    }
});
</script>

<template>
    <CmpLayout>
        <CmpToast ref="toastchild" />
        <UModal
            v-model:open="dialogOpen"
            :ui="{ content: 'w-[calc(100vw-2rem)] sm:max-w-[1200px]' }"
        >
            <template #content>
                <UCard
                    v-if="dialogOpen"
                    :ui="{ ring: '', divide: 'divide-y divide-gray-100 dark:divide-gray-800' }"
                >
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3
                                class="text-base font-semibold leading-6 text-gray-900 dark:text-white"
                            >
                                {{ dialogHeader }}
                            </h3>
                            <UButton
                                color="gray"
                                variant="ghost"
                                icon="i-heroicons-x-mark-20-solid"
                                class="-my-1"
                                @click="dialogOpen = false"
                            />
                        </div>
                    </template>
                    <DialogRoleMan
                        v-model:dialogOpen="dialogOpen"
                        :dialogData="dialogData"
                        :dialogTypeCreate="dialogData === null ? true : false"
                    />
                </UCard>
            </template>
        </UModal>
        <div class="mx-auto w-full max-w-6xl space-y-5">
            <div
                class="rounded-xl border border-gray-200 bg-gradient-to-r from-white to-gray-50 p-6 shadow-sm"
            >
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-4">
                    <div class="flex-1">
                        <h2 class="text-4xl font-semibold tracking-tight text-gray-800">
                            {{ timeGreet + greetings }}
                        </h2>
                        <h3 class="mt-1 text-sm text-gray-600">Role Management</h3>
                    </div>

                    <div class="flex w-full justify-end md:w-auto">
                        <StdButton
                            variant="primary"
                            label="Create Role"
                            class="rounded-md bg-green-600 px-5 py-2.5 text-white hover:bg-green-700"
                            @click="openEditRoleDialog(null)"
                        />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center">
                    <div class="flex-1">
                        <h3 class="text-4xl font-bold text-gray-900">Role Management</h3>
                    </div>
                    <div class="w-full md:w-80">
                        <UInput v-model="q" placeholder="Filter roles..." />
                    </div>
                </div>

                <CmpCustomTable
                    v-model:sortBy="sortBy"
                    v-model:page="page"
                    :rows="sortedData"
                    :columns="columns"
                    :loading="loading"
                    :itemsPerPage="pageCount"
                >
                    <template #action-data="{ row }">
                        <div v-if="showViewButton(row.id)" class="flex justify-center">
                            <UButton
                                size="xl"
                                icon="i-heroicons-chevron-double-right"
                                class="bg-green-600 text-white hover:bg-green-700"
                                @click="openEditRoleDialog(row)"
                            />
                        </div>
                    </template>
                    <template #permissions_array-data="{ row }">
                        <div class="text-left">
                            {{ row.permissions_array.join(', ') }}
                        </div>
                    </template>
                </CmpCustomTable>
            </div>
        </div>
    </CmpLayout>
</template>
