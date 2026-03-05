<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { timeGreetings } from '../AppCommon';
import { useEchoStore } from '../AppState';
import { storeToRefs } from 'pinia';
import { api } from '../AppAxios';
import CmpToast from '../Components/CmpToast.vue';
import CmpLayout from '../Components/CmpLayout.vue';
import CmpCustomTable from '../Components/CmpCustomTable.vue';
import StdButton from '../Components/StdButton.vue';
import { useTableSort } from '../composables/useTableSort';

import DialogAiModelInstructionMan from '../DialogComponents/DialogAiModelInstructionMan.vue';
import type { EchoWithMethods } from '../types/echo';

interface AiModelInstructionDataInterface {
    id: string;
    name: string;
    key: string;
    instructions: string;
    enabled: boolean;
    scope: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
}

const echo = useEchoStore();
const { laravelEcho } = storeToRefs(echo);
const timeGreet = timeGreetings();
const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);

const instructionListData = ref<AiModelInstructionDataInterface[]>([]);
const loading = ref<boolean>(false);

// Table logic
const q = ref('');
const page = ref(1);
const pageCount = 10;
const columns = [
    { id: 'name', key: 'name', label: 'Name', sortable: true },
    { id: 'key', key: 'key', label: 'Key', sortable: true },
    { id: 'instructions', key: 'instructions', label: 'Instructions' },
    { id: 'enabled', key: 'enabled', label: 'Status', sortable: true },
    { id: 'actions', key: 'actions', label: 'Actions' },
];

const filteredRows = computed(() => {
    if (!q.value) {
        return instructionListData.value;
    }

    return instructionListData.value.filter((item) => {
        return Object.values(item).some((value) => {
            return String(value).toLowerCase().includes(q.value.toLowerCase());
        });
    });
});

// Use the sorting composable
const { sortBy, sortedData } = useTableSort(filteredRows);

const getInstructionListData = async () => {
    try {
        loading.value = true;
        const response = await api.getAiModelInstructionList();
        // Backend now returns { data: [...] } format
        instructionListData.value = (response.data?.data ||
            []) as unknown as AiModelInstructionDataInterface[];
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    } finally {
        loading.value = false;
    }
};

const dialogOpen = ref<boolean>(false);
const dialogData = ref<AiModelInstructionDataInterface | null>(null);
const dialogHeader = ref<string>('Create AI Model Instruction');

const openEditInstructionDialog = (data: AiModelInstructionDataInterface | null) => {
    dialogOpen.value = true;
    dialogData.value = data;
    if (data === null) {
        dialogHeader.value = 'Create AI Model Instruction';
    } else {
        dialogHeader.value = 'Edit AI Model Instruction';
    }
};

const importFromFile = async () => {
    try {
        loading.value = true;
        await api.postImportAiModelInstructionFromFile();
        toastchild.value?.toastDisplay({
            severity: 'success',
            summary: 'Imported',
            detail: 'Imported model instruction from file',
        });
        await getInstructionListData();
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    } finally {
        loading.value = false;
    }
};

const exportToFile = async () => {
    try {
        loading.value = true;
        await api.postExportAiModelInstructionToFile();
        toastchild.value?.toastDisplay({
            severity: 'success',
            summary: 'Exported',
            detail: 'Exported model instruction to file',
        });
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    getInstructionListData();

    // Subscribe to instruction create/update/delete events on settings.event
    try {
        const echoInstance = laravelEcho.value as unknown as EchoWithMethods | undefined;
        if (echoInstance && typeof echoInstance.private === 'function') {
            const ch = echoInstance.private('settings.event');
            ch.listen('InstructionCreated', () => getInstructionListData());
            ch.listen('InstructionUpdated', () => getInstructionListData());
            ch.listen('InstructionDeleted', () => getInstructionListData());
        }
    } catch (err) {
        console.debug('Echo private channel not available during mount.', err);
    }
});

onUnmounted(() => {
    try {
        const echoInstance = laravelEcho.value as unknown as EchoWithMethods | undefined;
        if (echoInstance && typeof echoInstance.leave === 'function') {
            echoInstance.leave('settings.event');
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
                    <DialogAiModelInstructionMan
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
                        <h3 class="mt-1 text-sm text-gray-600">AI Model Instruction Management</h3>
                    </div>
                    <div class="flex flex-wrap justify-end gap-2">
                        <StdButton variant="neutral" @click="importFromFile">
                            <span>Import from File</span>
                        </StdButton>
                        <StdButton variant="neutral" @click="exportToFile">
                            <span>Export to File</span>
                        </StdButton>
                        <StdButton
                            variant="primary"
                            label="Create Instruction"
                            class="rounded-md bg-green-600 px-5 py-2.5 text-white hover:bg-green-700"
                            @click="openEditInstructionDialog(null)"
                        />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center">
                    <div class="flex-1">
                        <h3 class="text-4xl font-bold text-gray-900">Instruction List</h3>
                    </div>
                    <div class="w-full md:w-80">
                        <UInput v-model="q" placeholder="Filter instructions..." />
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
                        {{ row.name }}
                    </template>
                    <template #key-data="{ row }">
                        <code class="rounded bg-gray-100 px-2 py-1 text-sm">{{ row.key }}</code>
                    </template>
                    <template #instructions-data="{ row }">
                        <div class="max-w-xs truncate text-sm" :title="row.instructions">
                            {{ row.instructions.substring(0, 100)
                            }}{{ row.instructions.length > 100 ? '...' : '' }}
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
                                @click="openEditInstructionDialog(row)"
                            />
                        </div>
                    </template>
                </CmpCustomTable>
            </div>
        </div>
    </CmpLayout>
</template>
