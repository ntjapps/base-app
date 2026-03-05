<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { timeGreetings } from '../AppCommon';

import { api } from '../AppAxios';
import CmpToast from '../Components/CmpToast.vue';
import CmpLayout from '../Components/CmpLayout.vue';
import CmpCustomTable from '../Components/CmpCustomTable.vue';
import StdButton from '../Components/StdButton.vue';
import { useTableSort } from '../composables/useTableSort';

import DialogTagMan from '../DialogComponents/DialogTagMan.vue';

interface TagDataInterface {
    id: string;
    name: string;
    description: string | null;
    color: string;
    enabled: boolean;
    is_system: boolean;
    created_at: string;
    updated_at: string;
}

const timeGreet = timeGreetings();
const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);

const tagListData = ref<TagDataInterface[]>([]);
const loading = ref<boolean>(false);

const q = ref('');
const page = ref(1);
const pageCount = 10;
const columns = [
    { id: 'name', key: 'name', label: 'Name', sortable: true },
    { id: 'description', key: 'description', label: 'Description', sortable: true },
    { id: 'color', key: 'color', label: 'Color', sortable: true },
    { id: 'enabled', key: 'enabled', label: 'Status', sortable: true },
    { id: 'actions', key: 'actions', label: 'Actions' },
];

const filteredRows = computed(() => {
    if (!q.value) {
        return tagListData.value;
    }

    return tagListData.value.filter((tag) => {
        return Object.values(tag).some((value) => {
            return String(value).toLowerCase().includes(q.value.toLowerCase());
        });
    });
});

// Use the sorting composable
const { sortBy, sortedData } = useTableSort(filteredRows);

const getTagListData = async () => {
    try {
        loading.value = true;
        const response = await api.getTagList();
        // Backend now returns { data: [...] } format
        tagListData.value = (response.data?.data || []) as unknown as TagDataInterface[];
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    } finally {
        loading.value = false;
    }
};

const dialogOpen = ref<boolean>(false);
const dialogData = ref<TagDataInterface | null>(null);
const dialogHeader = ref<string>('Create Tag');

const openEditTagDialog = (data: TagDataInterface | null) => {
    dialogOpen.value = true;
    dialogData.value = data;
    if (data === null) {
        dialogHeader.value = 'Create Tag';
    } else {
        dialogHeader.value = 'Edit Tag';
    }
};

onMounted(() => {
    getTagListData();
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
                    <DialogTagMan
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
                            {{ timeGreet }}
                        </h2>
                        <h3 class="mt-1 text-sm text-gray-600">Tag Management</h3>
                    </div>
                    <div class="flex w-full justify-end md:w-auto">
                        <StdButton
                            variant="primary"
                            label="Create Tag"
                            class="rounded-md bg-green-600 px-5 py-2.5 text-white hover:bg-green-700"
                            @click="openEditTagDialog(null)"
                        />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center">
                    <div class="flex-1">
                        <h3 class="text-4xl font-bold text-gray-900">Tag Management</h3>
                    </div>
                    <div class="w-full md:w-80">
                        <UInput v-model="q" placeholder="Filter tags..." />
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
                    <template #name-data="{ row }">
                        <div class="flex items-center gap-2">
                            <span>{{ row.name }}</span>
                            <UBadge
                                v-if="row.is_system"
                                size="sm"
                                color="info"
                                label="System"
                                class="ml-1"
                            />
                        </div>
                    </template>
                    <template #description-data="{ row }">
                        {{ row.description || '-' }}
                    </template>
                    <template #color-data="{ row }">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 rounded border border-surface-300 dark:border-surface-700"
                                :style="{ backgroundColor: row.color }"
                            ></div>
                            <span class="text-sm">{{ row.color }}</span>
                        </div>
                    </template>
                    <template #enabled-data="{ row }">
                        <div class="text-left">
                            <UButton
                                v-if="row.enabled"
                                size="sm"
                                color="success"
                                label="Enabled"
                                variant="soft"
                            />
                            <UButton
                                v-else
                                size="sm"
                                color="error"
                                label="Disabled"
                                variant="soft"
                            />
                        </div>
                    </template>
                    <template #actions-data="{ row }">
                        <div class="flex gap-2">
                            <UButton
                                size="sm"
                                color="gray"
                                variant="ghost"
                                icon="i-heroicons-pencil"
                                class="bg-green-600 text-white hover:bg-green-700"
                                :disabled="row.is_system"
                                :title="row.is_system ? 'System tags cannot be edited' : 'Edit tag'"
                                @click="openEditTagDialog(row)"
                            />
                        </div>
                    </template>
                </CmpCustomTable>
            </div>
        </div>
    </CmpLayout>
</template>
