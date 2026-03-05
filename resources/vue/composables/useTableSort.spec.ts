import { describe, it, expect } from 'vitest';
import { ref, computed } from 'vue';
import { useTableSort } from './useTableSort';

describe('useTableSort', () => {
    it('returns unsorted data when no sort column is set', () => {
        const data = ref([
            { id: 1, name: 'Charlie', age: 30 },
            { id: 2, name: 'Alice', age: 25 },
            { id: 3, name: 'Bob', age: 35 },
        ]);

        const { sortedData } = useTableSort(data);

        expect(sortedData.value).toEqual(data.value);
    });

    it('sorts data ascending by string column', () => {
        const data = ref([
            { id: 1, name: 'Charlie', age: 30 },
            { id: 2, name: 'Alice', age: 25 },
            { id: 3, name: 'Bob', age: 35 },
        ]);

        const { sortBy, sortedData } = useTableSort(data);
        sortBy.value = { column: 'name', direction: 'asc' };

        expect(sortedData.value.map((r) => r.name)).toEqual(['Alice', 'Bob', 'Charlie']);
    });

    it('sorts data descending by string column', () => {
        const data = ref([
            { id: 1, name: 'Charlie', age: 30 },
            { id: 2, name: 'Alice', age: 25 },
            { id: 3, name: 'Bob', age: 35 },
        ]);

        const { sortBy, sortedData } = useTableSort(data);
        sortBy.value = { column: 'name', direction: 'desc' };

        expect(sortedData.value.map((r) => r.name)).toEqual(['Charlie', 'Bob', 'Alice']);
    });

    it('sorts data ascending by numeric column', () => {
        const data = ref([
            { id: 1, name: 'Charlie', age: 30 },
            { id: 2, name: 'Alice', age: 25 },
            { id: 3, name: 'Bob', age: 35 },
        ]);

        const { sortBy, sortedData } = useTableSort(data);
        sortBy.value = { column: 'age', direction: 'asc' };

        expect(sortedData.value.map((r) => r.age)).toEqual([25, 30, 35]);
    });

    it('sorts data descending by numeric column', () => {
        const data = ref([
            { id: 1, name: 'Charlie', age: 30 },
            { id: 2, name: 'Alice', age: 25 },
            { id: 3, name: 'Bob', age: 35 },
        ]);

        const { sortBy, sortedData } = useTableSort(data);
        sortBy.value = { column: 'age', direction: 'desc' };

        expect(sortedData.value.map((r) => r.age)).toEqual([35, 30, 25]);
    });

    it('handles null and undefined values', () => {
        const data = ref([
            { id: 1, name: 'Charlie', age: 30 },
            { id: 2, name: null, age: 25 },
            { id: 3, name: 'Alice', age: null },
            { id: 4, name: undefined, age: 35 },
        ]);

        const { sortBy, sortedData } = useTableSort(data);
        sortBy.value = { column: 'name', direction: 'asc' };

        // null/undefined should be sorted to the end
        const names = sortedData.value.map((r) => r.name);
        expect(names[0]).toBe('Alice');
        expect(names[1]).toBe('Charlie');
        expect([null, undefined]).toContainEqual(names[2]);
        expect([null, undefined]).toContainEqual(names[3]);
    });

    it('works with computed data sources', () => {
        const allData = ref([
            { id: 1, name: 'Charlie', active: true },
            { id: 2, name: 'Alice', active: false },
            { id: 3, name: 'Bob', active: true },
            { id: 4, name: 'David', active: false },
        ]);

        const filteredData = computed(() => allData.value.filter((r) => r.active));

        const { sortBy, sortedData } = useTableSort(filteredData);
        sortBy.value = { column: 'name', direction: 'asc' };

        expect(sortedData.value.map((r) => r.name)).toEqual(['Bob', 'Charlie']);
    });

    it('integrates with pagination pattern', () => {
        const data = ref([
            { id: 1, name: 'E' },
            { id: 2, name: 'D' },
            { id: 3, name: 'C' },
            { id: 4, name: 'B' },
            { id: 5, name: 'A' },
        ]);

        const { sortBy, sortedData } = useTableSort(data);
        sortBy.value = { column: 'name', direction: 'asc' };

        const page = ref(1);
        const pageSize = 2;

        // Simulate pagination slice
        const paginatedData = computed(() => {
            const start = (page.value - 1) * pageSize;
            const end = start + pageSize;
            return sortedData.value.slice(start, end);
        });

        // Page 1 should show first 2 sorted items
        expect(paginatedData.value.map((r) => r.name)).toEqual(['A', 'B']);

        // Page 2 should show next 2 sorted items
        page.value = 2;
        expect(paginatedData.value.map((r) => r.name)).toEqual(['C', 'D']);

        // Page 3 should show last item
        page.value = 3;
        expect(paginatedData.value.map((r) => r.name)).toEqual(['E']);
    });
});
