<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { timeGreetings } from '../AppCommon';
import { useMainStore } from '../AppState';
import { api } from '../AppAxios';
import CmpLayout from '../Components/CmpLayout.vue';
import CmpCustomTable from '../Components/CmpCustomTable.vue';
import StdButton from '../Components/StdButton.vue';

const timeGreet = timeGreetings();
const main = useMainStore();
const { userName, appName } = storeToRefs(main);

type ServerLogResponseType = {
    current_page: number;
    data: Array<ServerLogDataType>;
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
};

type ServerLogType = {
    id: string;
    message: string;
    channel: string;
    level: number;
    level_name: string;
    datetime: string;
    context: object | null;
    extra: object | null;
    created_at: string;
    updated_at: string;
};

type ServerLogDataType = Array<ServerLogType>;

type LogLevelSelectType = Array<{
    label: string;
    value: string;
}>;

const loadingstat = ref<boolean>(true);
const serverLogResponse = ref<ServerLogResponseType | null>(null);
const serverLogData = computed(() => serverLogResponse.value?.data ?? []);
const totalRecords = computed(() => serverLogResponse.value?.total ?? 0);
const rowsPerPage = computed(() => serverLogResponse.value?.per_page ?? 20);
const currentPage = ref(1);

const formatDateForInput = (date: Date) => {
    return date.toISOString().split('T')[0];
};

const dateStartData = ref<string>(formatDateForInput(new Date()));
const dateEndData = ref<string>(formatDateForInput(new Date()));

const logLevelSelect = ref<LogLevelSelectType>([
    { label: 'All', value: 'all' },
    { label: 'Debug', value: 'debug' },
    { label: 'Info', value: 'info' },
    { label: 'Notice', value: 'notice' },
    { label: 'Warning', value: 'warning' },
    { label: 'Error', value: 'error' },
    { label: 'Critical', value: 'critical' },
    { label: 'Alert', value: 'alert' },
    { label: 'Emergency', value: 'emergency' },
]);
const logLevelData = ref('all');
const logMessageData = ref<string>('');
const logExtraData = ref<string>('');

const columns = [
    { id: 'created_at', key: 'created_at', label: 'Log Date' },
    { id: 'message', key: 'message', label: 'Log Message' },
    { id: 'context', key: 'context', label: 'Log Extra' },
    { id: 'level_name', key: 'level_name', label: 'Level Name' },
];

// helper to build payload for API
const buildPayload = (page?: number, per_page?: number) => ({
    date_start: new Date(dateStartData.value), // Convert string back to Date if needed, or API might accept string
    date_end: new Date(dateEndData.value),
    log_level: logLevelData.value,
    log_message: logMessageData.value,
    log_extra: logExtraData.value,
    ...(typeof page === 'number' ? { page } : {}),
    ...(typeof per_page === 'number' ? { per_page } : {}),
});

const getServerLogData = async (page?: number, per_page?: number) => {
    try {
        loadingstat.value = true;
        const payload = buildPayload(page, per_page);
        const response = await api.getServerLogs(payload);
        serverLogResponse.value = response.data as ServerLogResponseType;
        if (serverLogResponse.value?.current_page) {
            currentPage.value = serverLogResponse.value.current_page;
        }
    } catch (error) {
        console.error('Failed to fetch server logs:', error);
    } finally {
        loadingstat.value = false;
    }
};

watch(currentPage, (newPage) => {
    getServerLogData(newPage, rowsPerPage.value);
});

onMounted(() => {
    getServerLogData(1, 20);
});

const onSearch = () => {
    // Reset to page 1 on search
    if (currentPage.value === 1) {
        getServerLogData(1, rowsPerPage.value);
    } else {
        currentPage.value = 1;
    }
};
</script>

<template>
    <CmpLayout>
        <div class="mx-auto w-full max-w-6xl space-y-5">
            <div
                class="rounded-xl border border-gray-200 bg-gradient-to-r from-white to-gray-50 p-6 shadow-sm"
            >
                <div class="flex flex-col gap-2 md:flex-row md:justify-between">
                    <div>
                        <h2 class="text-4xl font-semibold tracking-tight text-gray-800">
                            {{ timeGreet + userName }}
                        </h2>
                        <h3 class="mt-1 text-sm text-gray-600">Server Log in {{ appName }}</h3>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col md:flex-row my-2 gap-2">
                    <div class="flex w-full px-1">
                        <div class="w-28 my-auto text-sm m-auto">Date Start</div>
                        <div class="flex w-full text-sm m-auto">
                            <UInput v-model="dateStartData" type="date" class="w-full" />
                        </div>
                    </div>
                    <div class="flex w-full px-1 mt-2 md:mt-0">
                        <div class="w-28 my-auto text-sm m-auto">Date End</div>
                        <div class="flex w-full text-sm m-auto">
                            <UInput v-model="dateEndData" type="date" class="w-full" />
                        </div>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row my-2 gap-2">
                    <div class="flex w-full px-1">
                        <div class="w-28 my-auto text-sm m-auto">Log Level Minimal</div>
                        <div class="flex w-full text-sm m-auto">
                            <USelectMenu
                                v-model="logLevelData"
                                :options="logLevelSelect"
                                optionAttribute="label"
                                valueAttribute="value"
                                placeholder="Select a Log Level"
                                class="w-full"
                            />
                        </div>
                    </div>
                    <div class="flex w-full px-1 mt-2 md:mt-0">
                        <div class="w-28 my-auto text-sm m-auto">Log Message</div>
                        <div class="flex w-full text-sm m-auto">
                            <UInput
                                v-model="logMessageData"
                                class="w-full"
                                placeholder="Log Message"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row my-2 gap-2">
                    <div class="flex w-full px-1">
                        <div class="w-28 my-auto text-sm m-auto">Log Extra</div>
                        <div class="flex w-full text-sm m-auto">
                            <UInput v-model="logExtraData" class="w-full" placeholder="Log Extra" />
                        </div>
                    </div>
                    <div class="flex w-full mt-2 md:mt-0">
                        <div class="w-28 my-auto text-sm m-auto"></div>
                        <div class="flex w-full text-sm m-auto">
                            <StdButton
                                variant="primary"
                                class="rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700"
                                :disabled="loadingstat"
                                @click="onSearch"
                                >Search</StdButton
                            >
                        </div>
                    </div>
                </div>

                <CmpCustomTable
                    v-model:page="currentPage"
                    :rows="serverLogData"
                    :columns="columns"
                    :loading="loadingstat"
                    :itemsPerPage="rowsPerPage"
                    :total="totalRecords"
                    serverSide
                >
                    <template #created_at-data="{ row }">
                        {{ new Date(row.created_at).toLocaleString('en-UK') }}
                    </template>
                    <template #context-data="{ row }">
                        <div
                            class="whitespace-pre-wrap font-mono text-xs max-w-xs overflow-hidden text-ellipsis"
                        >
                            {{ JSON.stringify(row.context) }}
                        </div>
                    </template>
                </CmpCustomTable>
            </div>
        </div>
    </CmpLayout>
</template>
