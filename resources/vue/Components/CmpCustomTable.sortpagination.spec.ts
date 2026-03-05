import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref, computed, defineComponent, nextTick } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import CmpCustomTable from './CmpCustomTable.vue';

type SortState = { column: string | null; direction: 'asc' | 'desc' };
type DataRow = Record<string, string | number>;

describe('CmpCustomTable sorting + pagination integration', () => {
    it('sorts all data before pagination', async () => {
        // Simulate a parent component with sorting and pagination
        const allData: DataRow[] = [
            { id: 1, name: 'E' },
            { id: 2, name: 'D' },
            { id: 3, name: 'C' },
            { id: 4, name: 'B' },
            { id: 5, name: 'A' },
        ];

        const Parent = defineComponent({
            components: { CmpCustomTable },
            setup() {
                const page = ref(1);
                const pageCount = 2;
                const sortBy = ref<SortState>({ column: null, direction: 'asc' });
                const columns = [{ id: 'name', key: 'name', label: 'Name', sortable: true }];

                // Sort the data
                const sortedData = computed(() => {
                    if (!sortBy.value.column) return allData;

                    return [...allData].sort((a, b) => {
                        const column = sortBy.value.column as keyof DataRow;
                        const aVal = a[column];
                        const bVal = b[column];

                        if (typeof aVal === 'string' && typeof bVal === 'string') {
                            return sortBy.value.direction === 'asc'
                                ? aVal.localeCompare(bVal)
                                : bVal.localeCompare(aVal);
                        }
                        return 0;
                    });
                });

                // Paginate after sorting
                const rows = computed(() => {
                    return sortedData.value.slice(
                        (page.value - 1) * pageCount,
                        page.value * pageCount,
                    );
                });

                const next = () => (page.value += 1);
                const reset = () => (page.value = 1);

                return { rows, columns, next, reset, sortBy };
            },
            template: `
                <div>
                  <button data-test="next" @click="next">Next</button>
                  <button data-test="reset" @click="reset">Reset</button>
                  <CmpCustomTable :columns="columns" :rows="rows" v-model:sortBy="sortBy" />
                </div>
            `,
        });

        const router = createRouter({
            history: createWebHistory(),
            routes: [],
        });

        const wrapper = mount(Parent, {
            global: {
                stubs: {
                    UIcon: true,
                },
                plugins: [router],
            },
        });

        // Initial state: page 1, unsorted (E, D on page 1)
        expect(wrapper.html()).toContain('E');
        expect(wrapper.html()).toContain('D');
        expect(wrapper.html()).not.toContain('A');

        // Click the sortable column header to sort ascending
        const columnHeader = wrapper.findAll('th')[0]; // First column (Name)
        await columnHeader.trigger('click');
        await nextTick();

        // After sorting: page 1 should show A, B (first 2 sorted items)
        expect(wrapper.html()).toContain('A');
        expect(wrapper.html()).toContain('B');
        expect(wrapper.html()).not.toContain('C');
        expect(wrapper.html()).not.toContain('E');

        // Go to page 2
        await wrapper.find('[data-test="next"]').trigger('click');
        await nextTick();

        // Page 2 should show C, D (next 2 sorted items)
        expect(wrapper.html()).toContain('C');
        expect(wrapper.html()).toContain('D');
        expect(wrapper.html()).not.toContain('A');
        expect(wrapper.html()).not.toContain('E');

        // Go to page 3
        await wrapper.find('[data-test="next"]').trigger('click');
        await nextTick();

        // Page 3 should show E (last sorted item)
        expect(wrapper.html()).toContain('E');
        expect(wrapper.html()).not.toContain('A');
        expect(wrapper.html()).not.toContain('B');
        expect(wrapper.html()).not.toContain('C');
        expect(wrapper.html()).not.toContain('D');

        // Click column header again to sort descending
        await wrapper.find('[data-test="reset"]').trigger('click'); // Back to page 1
        await nextTick();
        await columnHeader.trigger('click'); // Toggle sort to desc
        await nextTick();

        // Page 1 with desc sort should show E, D
        expect(wrapper.html()).toContain('E');
        expect(wrapper.html()).toContain('D');
        expect(wrapper.html()).not.toContain('A');
        expect(wrapper.html()).not.toContain('B');
    });

    it('maintains sort state when switching pages', async () => {
        const allData: DataRow[] = [
            { id: 1, name: 'Z', value: 10 },
            { id: 2, name: 'Y', value: 20 },
            { id: 3, name: 'X', value: 30 },
        ];

        const Parent = defineComponent({
            components: { CmpCustomTable },
            setup() {
                const page = ref(1);
                const pageCount = 2;
                const sortBy = ref<SortState>({ column: 'name', direction: 'asc' });
                const columns = [
                    { id: 'name', key: 'name', label: 'Name', sortable: true },
                    { id: 'value', key: 'value', label: 'Value', sortable: true },
                ];

                const sortedData = computed(() => {
                    if (!sortBy.value.column) return allData;

                    return [...allData].sort((a, b) => {
                        const column = sortBy.value.column as keyof DataRow;
                        const aVal = a[column];
                        const bVal = b[column];

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
                });

                const rows = computed(() => {
                    return sortedData.value.slice(
                        (page.value - 1) * pageCount,
                        page.value * pageCount,
                    );
                });

                const next = () => (page.value += 1);

                return { rows, columns, next, sortBy };
            },
            template: `
                <div>
                  <button @click="next">Next</button>
                  <CmpCustomTable :columns="columns" :rows="rows" v-model:sortBy="sortBy" />
                  <div data-test="sort-state">{{ sortBy.column }} {{ sortBy.direction }}</div>
                </div>
            `,
        });

        const router = createRouter({
            history: createWebHistory(),
            routes: [],
        });

        const wrapper = mount(Parent, {
            global: {
                stubs: {
                    UIcon: true,
                },
                plugins: [router],
            },
        });

        // Initial: sorted by name asc, page 1 shows X, Y
        expect(wrapper.html()).toContain('X');
        expect(wrapper.html()).toContain('Y');
        expect(wrapper.find('[data-test="sort-state"]').text()).toBe('name asc');

        // Go to page 2
        await wrapper.find('button').trigger('click');
        await nextTick();

        // Page 2 shows Z, sort state preserved
        expect(wrapper.html()).toContain('Z');
        expect(wrapper.html()).not.toContain('X');
        expect(wrapper.find('[data-test="sort-state"]').text()).toBe('name asc');
    });
});
