import { ref, computed, type Ref } from 'vue';

export interface SortState {
    column: string | null;
    direction: 'asc' | 'desc';
}

export interface Row {
    id?: string | number;
    [key: string]: unknown;
}

/**
 * Composable for handling table sorting logic
 * @param data - Reactive array of data to sort
 * @returns sortBy state and sortedData computed property
 */
export function useTableSort<T extends Row>(data: Ref<T[]>) {
    const sortBy = ref<SortState>({ column: null, direction: 'asc' });

    const sortedData = computed(() => {
        const dataArray = data.value ?? [];
        if (!sortBy.value.column) return dataArray;

        const sorted = [...dataArray].sort((a, b) => {
            const aVal = a[sortBy.value.column!];
            const bVal = b[sortBy.value.column!];

            if (aVal === null || aVal === undefined) return 1;
            if (bVal === null || bVal === undefined) return -1;

            if (typeof aVal === 'string' && typeof bVal === 'string') {
                return sortBy.value.direction === 'asc'
                    ? aVal.localeCompare(bVal)
                    : bVal.localeCompare(aVal);
            }

            if (sortBy.value.direction === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });

        return sorted;
    });

    return {
        sortBy,
        sortedData,
    };
}
