<script setup lang="ts">
import { computed, ref, watch, onMounted, nextTick } from 'vue';

export interface Column {
    id: string;
    key: string;
    label: string;
    sortable?: boolean;
    filterable?: boolean;
}

export interface Row {
    id?: string | number;
    [key: string]: unknown;
}

export interface SortState {
    column: string | null;
    direction: 'asc' | 'desc';
}

export interface CustomTableProps {
    columns: Column[];
    rows: Row[];
    loading?: boolean;
    modelValue?: Row[];
    sortBy?: SortState;
    page?: number;
    itemsPerPage?: number;
    total?: number;
    serverSide?: boolean;
    stickyFirstColumns?: number;
}

const props = withDefaults(defineProps<CustomTableProps>(), {
    loading: false,
    modelValue: undefined,
    sortBy: () => ({ column: null, direction: 'asc' }),
    page: 1,
    itemsPerPage: 10,
    total: undefined,
    serverSide: false,
    stickyFirstColumns: 0,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: Row[]): void;
    (e: 'update:sortBy', value: SortState): void;
    (e: 'update:page', value: number): void;
}>();

const internalPage = ref(props.page);
const columnWidths = ref<number[]>([]);
const tableRec = ref<HTMLElement | null>(null);

watch(
    () => props.page,
    (val) => {
        if (val !== internalPage.value) internalPage.value = val;
    },
);

watch(internalPage, (val) => {
    emit('update:page', val);
});

// Update column widths for sticky positioning
const updateColumnWidths = () => {
    if (!tableRec.value) return;
    const ths = tableRec.value.querySelectorAll('thead th');
    const widths: number[] = [];
    ths.forEach((th) => {
        widths.push(th.getBoundingClientRect().width);
    });
    columnWidths.value = widths;
};

onMounted(() => {
    nextTick(() => {
        updateColumnWidths();
        // Optional: Add resize observer
        if (tableRec.value && typeof ResizeObserver !== 'undefined') {
            const observer = new ResizeObserver(updateColumnWidths);
            observer.observe(tableRec.value);
        }
    });
});

const getStickyStyle = (index: number) => {
    if (index >= props.stickyFirstColumns) return {};

    // Calculate sum of widths of previous columns
    // We must account for the checkbox column if it exists (modelValue !== undefined)
    // If checkbox exists, it is index 0 in the DOM but generic logic might differ.
    // Let's assume index passed here excludes checkbox column if we iterate columns.
    // But existing template has <th> for checkbox separately.
    // So 'index' from v-for columns relates to props.columns.

    let left = 0;
    const hasCheckbox = props.modelValue !== undefined;
    const checkboxWidth = hasCheckbox && columnWidths.value.length > 0 ? columnWidths.value[0] : 0;

    // Add checkbox width if exists
    if (hasCheckbox) {
        left += checkboxWidth;
    }

    // Add widths of previous columns
    for (let i = 0; i < index; i++) {
        // Offset by 1 in columnWidths if checkbox exists
        const widthIndex = hasCheckbox ? i + 1 : i;
        if (widthIndex < columnWidths.value.length) {
            left += columnWidths.value[widthIndex];
        }
    }

    return {
        left: `${left}px`,
        position: 'sticky',
        zIndex: 0,
    };
};
// Checkbox column sticky style
const getCheckboxStickyStyle = () => {
    if (props.stickyFirstColumns > 0) {
        return {
            left: '0px',
            position: 'sticky',
            zIndex: 0,
        };
    }
    return {};
};

// Handle sorting - emit event to parent instead of handling internally
const toggleSort = (column: Column) => {
    if (!column.sortable) return;

    const currentSort = props.sortBy || { column: null, direction: 'asc' as const };

    if (currentSort.column === column.key) {
        emit('update:sortBy', {
            column: column.key,
            direction: currentSort.direction === 'asc' ? 'desc' : 'asc',
        });
    } else {
        emit('update:sortBy', {
            column: column.key,
            direction: 'asc',
        });
    }
};

// Just use rows as-is (parent handles sorting and pagination)
const displayedRows = computed(() => {
    const data = props.rows ?? [];
    if (props.serverSide) return data;
    return data.slice(
        (internalPage.value - 1) * props.itemsPerPage,
        internalPage.value * props.itemsPerPage,
    );
});

// Selection handling
const selectedRows = computed({
    get: () => props.modelValue || [],
    set: (value: Row[]) => emit('update:modelValue', value),
});

const isRowSelected = (row: Row) => {
    if (!props.modelValue) return false;
    return selectedRows.value.some((selected) => selected.id === row.id);
};

const toggleRowSelection = (row: Row) => {
    if (!props.modelValue) return;

    const index = selectedRows.value.findIndex((selected) => selected.id === row.id);
    if (index > -1) {
        const newSelection = [...selectedRows.value];
        newSelection.splice(index, 1);
        emit('update:modelValue', newSelection);
    } else {
        emit('update:modelValue', [...selectedRows.value, row]);
    }
};

const isAllSelected = computed(() => {
    const data = props.rows ?? [];
    if (!props.modelValue || data.length === 0) return false;
    const selectedIds = new Set(selectedRows.value.map((r) => r.id));
    return data.every((row) => selectedIds.has(row.id));
});

const isIndeterminate = computed(() => {
    const data = props.rows ?? [];
    if (!props.modelValue || data.length === 0) return false;
    const selectedIds = new Set(selectedRows.value.map((r) => r.id));
    const selectedCount = data.filter((row) => selectedIds.has(row.id)).length;
    return selectedCount > 0 && selectedCount < data.length;
});

const toggleSelectAll = () => {
    if (!props.modelValue) return;

    const data = props.rows ?? [];
    const allIds = new Set(data.map((r) => r.id));
    let newSelection = [...selectedRows.value];

    if (isAllSelected.value) {
        // Deselect all rows
        newSelection = newSelection.filter((row) => !allIds.has(row.id));
    } else {
        // Select all rows
        const selectedIds = new Set(newSelection.map((r) => r.id));
        data.forEach((row) => {
            if (!selectedIds.has(row.id)) {
                newSelection.push(row);
            }
        });
    }
    emit('update:modelValue', newSelection);
};
</script>

<template>
    <div ref="tableRec" class="relative overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <div
            v-if="loading"
            class="absolute inset-0 z-30 flex items-center justify-center bg-white/75"
        >
            <div class="flex items-center gap-2">
                <div
                    class="w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin"
                ></div>
                <span class="text-sm text-gray-600">Loading...</span>
            </div>
        </div>
        <table class="w-full text-sm text-left rtl:text-right text-gray-700">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                <tr>
                    <th
                        v-if="modelValue !== undefined"
                        scope="col"
                        class="bg-gray-50 px-3 py-3.5"
                        :style="getCheckboxStickyStyle()"
                    >
                        <UCheckbox
                            :modelValue="isAllSelected"
                            :indeterminate="isIndeterminate"
                            @update:modelValue="toggleSelectAll"
                        />
                    </th>
                    <th
                        v-for="(column, index) in columns"
                        :key="column.id"
                        scope="col"
                        class="bg-gray-50 px-3 py-3.5"
                        :class="{
                            'cursor-pointer select-none hover:bg-gray-100': column.sortable,
                            'border-r border-gray-200': index < stickyFirstColumns,
                        }"
                        :style="getStickyStyle(index)"
                        @click="toggleSort(column)"
                    >
                        <div class="flex items-center gap-1">
                            <slot
                                :name="`${column.key}-header`"
                                :column="column"
                                :sortBy="sortBy"
                                :toggleSort="toggleSort"
                            >
                                <span>{{ column.label }}</span>
                                <span
                                    v-if="column.sortable && sortBy && sortBy.column === column.key"
                                    class="text-primary-500"
                                >
                                    <UIcon
                                        v-if="sortBy.direction === 'asc'"
                                        name="i-heroicons-chevron-up"
                                        class="w-4 h-4"
                                    />
                                    <UIcon v-else name="i-heroicons-chevron-down" class="w-4 h-4" />
                                </span>
                                <span v-else-if="column.sortable" class="text-gray-400">
                                    <UIcon name="i-heroicons-chevron-up-down" class="w-4 h-4" />
                                </span>
                            </slot>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="displayedRows.length === 0 && !loading">
                    <td
                        :colspan="columns.length + (modelValue !== undefined ? 1 : 0)"
                        class="px-3 py-8 text-center text-gray-500"
                    >
                        No data available
                    </td>
                </tr>
                <tr
                    v-for="(row, rowIndex) in displayedRows"
                    :key="row.id || rowIndex"
                    class="border-b border-gray-100 bg-white hover:bg-gray-50"
                    :class="{ 'bg-primary-50': isRowSelected(row) }"
                >
                    <td
                        v-if="modelValue !== undefined"
                        class="bg-white px-3 py-3"
                        :style="getCheckboxStickyStyle()"
                    >
                        <UCheckbox
                            :modelValue="isRowSelected(row)"
                            @update:modelValue="() => toggleRowSelection(row)"
                        />
                    </td>
                    <td
                        v-for="(column, index) in columns"
                        :key="column.id"
                        class="bg-white px-3 py-3"
                        :class="{
                            'border-r border-gray-200': index < stickyFirstColumns,
                        }"
                        :style="getStickyStyle(index)"
                    >
                        <slot :name="`${column.key}-data`" :row="row" :column="column">
                            {{ row[column.key] }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="flex justify-center border-t border-gray-200 px-3 py-3.5">
            <UPagination
                v-model:page="internalPage"
                :itemsPerPage="itemsPerPage"
                :total="total ?? rows.length"
            />
        </div>
    </div>
</template>
