<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { storeToRefs } from 'pinia';
import { timeGreetings } from '../AppCommon';
import { useMainStore } from '../AppState';
import { api } from '../AppAxios';
import CmpLayout from '../Components/CmpLayout.vue';

import DataTable from '../volt/DataTable.vue';
import Column from 'primevue/column';
import DatePicker from '../volt/DatePicker.vue';
import Select from '../volt/Select.vue';
import InputText from '../volt/InputText.vue';

const props = defineProps<{
    appName: string;
    greetings: string;
    expandedKeysProps: string;
}>();
const timeGreet = timeGreetings();
const main = useMainStore();
const { userName } = storeToRefs(main);

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
const first = computed(() => {
    const resp = serverLogResponse.value;
    if (!resp) return 0;
    return ((resp.current_page ?? 1) - 1) * (resp.per_page ?? 20);
});
const dateStartData = ref<Date>(new Date());
const dateEndData = ref<Date>(new Date());
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
const logLevelData = ref<string>('all');
const logMessageData = ref<string>('');
const logExtraData = ref<string>('');

// helper to build payload for API
const buildPayload = (page?: number, per_page?: number) => ({
    date_start: dateStartData.value,
    date_end: dateEndData.value,
    log_level: logLevelData.value,
    log_message: logMessageData.value,
    log_extra: logExtraData.value,
    ...(page ? { page } : {}),
    ...(per_page ? { per_page } : {}),
});

const getServerLogData = async (page?: number, per_page?: number) => {
    try {
        loadingstat.value = true;
        const payload = buildPayload(page, per_page);
        const response = await api.getServerLogs(payload);
        serverLogResponse.value = response.data.data ?? response.data;
    } catch (error) {
        console.error('Failed to fetch server logs:', error);
    } finally {
        loadingstat.value = false;
    }
};

const onPage = async (event: { first: number; rows: number; page: number }) => {
    // PrimeVue page is 0-based; Laravel expects 1-based
    const page = (event.page ?? 0) + 1;
    const perPage = event.rows;
    await getServerLogData(page, perPage);
};

onMounted(() => {
    getServerLogData(1, 20);
    main.updateExpandedKeysMenu(props.expandedKeysProps);
});
</script>

<template>
    <CmpLayout>
        <div
            class="my-2 md:my-3 mx-2 md:mx-5 p-3 md:p-5 bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg"
        >
            <div class="flex flex-col gap-2 md:gap-0 md:flex-row justify-between">
                <div>
                    <h2 class="title-font font-bold">
                        {{ timeGreet + userName }}
                    </h2>
                    <h3 class="title-font">Server Log in {{ appName }}</h3>
                </div>
            </div>
        </div>
        <div
            class="my-2 md:my-3 mx-2 md:mx-5 p-3 md:p-5 bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg"
        >
            <div class="flex flex-col md:flex-row my-2 gap-2">
                <div class="flex w-full px-1">
                    <div class="w-28 my-auto text-sm m-auto">Date Start</div>
                    <div class="flex w-full text-sm m-auto">
                        <DatePicker v-model="dateStartData" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                </div>
                <div class="flex w-full px-1 mt-2 md:mt-0">
                    <div class="w-28 my-auto text-sm m-auto">Date End</div>
                    <div class="flex w-full text-sm m-auto">
                        <DatePicker v-model="dateEndData" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                </div>
            </div>
            <div class="flex flex-col md:flex-row my-2 gap-2">
                <div class="flex w-full px-1">
                    <div class="w-28 my-auto text-sm m-auto">Log Level Minimal</div>
                    <div class="flex w-full text-sm m-auto">
                        <Select
                            v-model="logLevelData"
                            :options="logLevelSelect"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Select a Log Level"
                            class="w-full"
                        />
                    </div>
                </div>
                <div class="flex w-full px-1 mt-2 md:mt-0">
                    <div class="w-28 my-auto text-sm m-auto">Log Message</div>
                    <div class="flex w-full text-sm m-auto">
                        <InputText
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
                        <InputText v-model="logExtraData" class="w-full" placeholder="Log Extra" />
                    </div>
                </div>
                <div class="flex w-full mt-2 md:mt-0">
                    <div class="w-28 my-auto text-sm m-auto"></div>
                    <div class="flex w-full text-sm m-auto">
                        <UButton size="xl" :disabled="loadingstat" @click="getServerLogData"
                            ><i class="pi pi-search" />Search</UButton
                        >
                    </div>
                </div>
            </div>
        </div>
        <div
            class="my-2 md:my-3 mx-2 md:mx-5 p-3 md:p-5 bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg overflow-x-auto"
        >
            <DataTable
                class="p-datatable-sm text-sm"
                :value="serverLogData"
                showGridlines
                paginator
                lazy
                :totalRecords="totalRecords"
                :rows="rowsPerPage"
                :first="first"
                :loading="loadingstat"
                @page="onPage"
            >
                <template #empty>
                    <div class="flex justify-center">No data found</div>
                </template>
                <template #loading>
                    <i class="pi pi-spin pi-spinner mr-2.5"></i> Loading data. Please wait.
                </template>

                <Column field="created_at" header="Log Date">
                    <template #body="slotProps">
                        {{ new Date(slotProps.data.created_at).toLocaleString('en-UK') }}
                    </template>
                </Column>
                <Column field="message" header="Log Message" />
                <Column field="context" header="Log Extra" />
                <Column field="level_name" header="Level Name" />
            </DataTable>
        </div>
    </CmpLayout>
</template>
