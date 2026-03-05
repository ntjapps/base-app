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

type PaginatedResponse<T> = {
    current_page: number;
    data: Array<T>;
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

type RouteAnalyticsRecord = {
    id: string;
    method: string;
    path: string;
    route_name: string | null;
    route_group: string | null;
    status_code: number;
    duration_ms: number;
    user_id: string | null;
    user_name: string | null;
    ip: string | null;
    user_agent: string | null;
    created_at: string;
    updated_at: string;
};

type RouteAnalyticsSummary = {
    total_hits: number;
    unique_users: number;
    top_endpoints: Array<{
        method: string;
        path: string;
        hits: number;
    }>;
    top_users: Array<{
        user_id: string;
        user_name: string | null;
        hits: number;
    }>;
};

type RouteAnalyticsResponse = {
    summary: RouteAnalyticsSummary;
    records: PaginatedResponse<RouteAnalyticsRecord>;
};

type MethodSelectType = Array<{
    label: string;
    value: string;
}>;

const loadingstat = ref<boolean>(true);
const analyticsResponse = ref<RouteAnalyticsResponse | null>(null);
const analyticsRecords = computed(() => analyticsResponse.value?.records?.data ?? []);
const totalRecords = computed(() => analyticsResponse.value?.records?.total ?? 0);
const rowsPerPage = computed(() => analyticsResponse.value?.records?.per_page ?? 20);
const currentPage = ref(1);
const summary = computed<RouteAnalyticsSummary>(() => ({
    total_hits: analyticsResponse.value?.summary?.total_hits ?? 0,
    unique_users: analyticsResponse.value?.summary?.unique_users ?? 0,
    top_endpoints: analyticsResponse.value?.summary?.top_endpoints ?? [],
    top_users: analyticsResponse.value?.summary?.top_users ?? [],
}));

const formatDateForInput = (date: Date) => {
    return date.toISOString().split('T')[0];
};

const dateStartData = ref<string>(formatDateForInput(new Date()));
const dateEndData = ref<string>(formatDateForInput(new Date()));
const routeData = ref<string>('');
const userNameData = ref<string>('');
const statusCodeData = ref<string>('');

const methodSelect = ref<MethodSelectType>([
    { label: 'All', value: 'all' },
    { label: 'GET', value: 'GET' },
    { label: 'POST', value: 'POST' },
    { label: 'PUT', value: 'PUT' },
    { label: 'PATCH', value: 'PATCH' },
    { label: 'DELETE', value: 'DELETE' },
]);
const methodData = ref<string>('all');

const columns = [
    { id: 'created_at', key: 'created_at', label: 'Time' },
    { id: 'method', key: 'method', label: 'Method' },
    { id: 'path', key: 'path', label: 'Endpoint' },
    { id: 'route_name', key: 'route_name', label: 'Route Name' },
    { id: 'status_code', key: 'status_code', label: 'Status' },
    { id: 'user_name', key: 'user_name', label: 'User' },
    { id: 'ip', key: 'ip', label: 'IP' },
    { id: 'duration_ms', key: 'duration_ms', label: 'Duration (ms)' },
];

const buildPayload = (page?: number, per_page?: number) => {
    const statusCode = statusCodeData.value ? Number(statusCodeData.value) : undefined;
    return {
        date_start: dateStartData.value ? new Date(dateStartData.value) : undefined,
        date_end: dateEndData.value ? new Date(dateEndData.value) : undefined,
        route: routeData.value || undefined,
        method: methodData.value === 'all' ? undefined : methodData.value,
        user_name: userNameData.value || undefined,
        status_code: Number.isNaN(statusCode) ? undefined : statusCode,
        ...(typeof page === 'number' ? { page } : {}),
        ...(typeof per_page === 'number' ? { per_page } : {}),
    };
};

const getRouteAnalytics = async (page?: number, per_page?: number) => {
    try {
        loadingstat.value = true;
        const payload = buildPayload(page, per_page);
        const response = await api.getRouteAnalytics(payload);
        analyticsResponse.value = response.data as RouteAnalyticsResponse;
        if (analyticsResponse.value?.records?.current_page) {
            currentPage.value = analyticsResponse.value.records.current_page;
        }
    } catch (error) {
        console.error('Failed to fetch route analytics:', error);
    } finally {
        loadingstat.value = false;
    }
};

watch(currentPage, (newPage) => {
    getRouteAnalytics(newPage, rowsPerPage.value);
});

onMounted(() => {
    getRouteAnalytics(1, 20);
});

const onSearch = () => {
    if (currentPage.value === 1) {
        getRouteAnalytics(1, rowsPerPage.value);
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
                        <h3 class="mt-1 text-sm text-gray-600">Route Analytics in {{ appName }}</h3>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="text-sm text-gray-600">Total Hits</div>
                    <div class="text-2xl font-semibold text-gray-900">
                        {{ summary.total_hits }}
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="text-sm text-gray-600">Unique Users</div>
                    <div class="text-2xl font-semibold text-gray-900">
                        {{ summary.unique_users }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="mb-2 text-sm font-semibold">Top Endpoints</div>
                    <div v-if="summary.top_endpoints.length === 0" class="text-sm text-gray-500">
                        No data
                    </div>
                    <ul class="space-y-1">
                        <li
                            v-for="item in summary.top_endpoints"
                            :key="`${item.method}-${item.path}`"
                        >
                            <span class="font-mono text-xs">{{ item.method }}</span>
                            <span class="text-sm"> {{ item.path }}</span>
                            <span class="text-xs text-gray-500"> ({{ item.hits }})</span>
                        </li>
                    </ul>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="mb-2 text-sm font-semibold">Top Users</div>
                    <div v-if="summary.top_users.length === 0" class="text-sm text-gray-500">
                        No data
                    </div>
                    <ul class="space-y-1">
                        <li v-for="item in summary.top_users" :key="item.user_id">
                            <span class="text-sm">{{ item.user_name || 'Unknown' }}</span>
                            <span class="text-xs text-gray-500"> ({{ item.hits }})</span>
                        </li>
                    </ul>
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
                        <div class="w-28 my-auto text-sm m-auto">Method</div>
                        <div class="flex w-full text-sm m-auto">
                            <USelectMenu
                                v-model="methodData"
                                :options="methodSelect"
                                optionAttribute="label"
                                valueAttribute="value"
                                placeholder="Select Method"
                                class="w-full"
                            />
                        </div>
                    </div>
                    <div class="flex w-full px-1 mt-2 md:mt-0">
                        <div class="w-28 my-auto text-sm m-auto">Status</div>
                        <div class="flex w-full text-sm m-auto">
                            <UInput
                                v-model="statusCodeData"
                                class="w-full"
                                placeholder="Status Code"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row my-2 gap-2">
                    <div class="flex w-full px-1">
                        <div class="w-28 my-auto text-sm m-auto">Route</div>
                        <div class="flex w-full text-sm m-auto">
                            <UInput v-model="routeData" class="w-full" placeholder="Route Path" />
                        </div>
                    </div>
                    <div class="flex w-full px-1 mt-2 md:mt-0">
                        <div class="w-28 my-auto text-sm m-auto">User</div>
                        <div class="flex w-full text-sm m-auto">
                            <UInput v-model="userNameData" class="w-full" placeholder="User Name" />
                        </div>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row my-2 gap-2">
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
                    :rows="analyticsRecords"
                    :columns="columns"
                    :loading="loadingstat"
                    :itemsPerPage="rowsPerPage"
                    :total="totalRecords"
                    serverSide
                >
                    <template #created_at-data="{ row }">
                        {{ new Date(row.created_at).toLocaleString('en-UK') }}
                    </template>
                    <template #route_name-data="{ row }">
                        {{ row.route_name || '-' }}
                    </template>
                    <template #user_name-data="{ row }">
                        {{ row.user_name || 'Guest' }}
                    </template>
                </CmpCustomTable>
            </div>
        </div>
    </CmpLayout>
</template>
