import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref, computed, defineComponent, nextTick } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import CmpCustomTable from './CmpCustomTable.vue';

const UPaginationStub = defineComponent({
    emits: ['update:page'],
    template:
        '<div><button data-type="page" value="1" @click="$emit(\'update:page\', 1)">1</button><button data-type="page" value="2" @click="$emit(\'update:page\', 2)">2</button></div>',
});

describe('CmpCustomTable pagination integration', () => {
    it('updates displayed rows when parent page changes', async () => {
        const all = [
            { id: 1, name: 'A' },
            { id: 2, name: 'B' },
            { id: 3, name: 'C' },
            { id: 4, name: 'D' },
        ];

        const Parent = defineComponent({
            components: { CmpCustomTable },
            setup() {
                const page = ref(1);
                const pageCount = 2;
                const rows = computed(() => {
                    return all.slice((page.value - 1) * pageCount, page.value * pageCount);
                });
                const next = () => (page.value += 1);
                const columns = [{ id: 'name', key: 'name', label: 'Name' }];
                return { rows, columns, next };
            },
            template: `
                <div>
                  <button @click="next">Next</button>
                  <CmpCustomTable :columns="columns" :rows="rows" />
                </div>
            `,
        });

        const router = createRouter({
            history: createWebHistory(),
            routes: [],
        });

        const wrapper = mount(Parent, {
            global: {
                stubs: { UIcon: true, UPagination: UPaginationStub },
                plugins: [router],
            },
        });

        expect(wrapper.html()).toContain('A');
        expect(wrapper.html()).toContain('B');
        expect(wrapper.html()).not.toContain('C');

        await wrapper.get('button').trigger('click');
        await nextTick();

        expect(wrapper.html()).toContain('C');
        expect(wrapper.html()).toContain('D');
        expect(wrapper.html()).not.toContain('A');
    });

    it('updates displayed rows when UPagination inside component is clicked', async () => {
        const rows = [
            { id: 1, name: 'A' },
            { id: 2, name: 'B' },
            { id: 3, name: 'C' },
            { id: 4, name: 'D' },
        ];

        const columns = [{ id: 'name', key: 'name', label: 'Name' }];

        const router = createRouter({
            history: createWebHistory(),
            routes: [],
        });

        const wrapper = mount(CmpCustomTable, {
            props: { columns, rows, itemsPerPage: 2 },
            global: {
                stubs: { UIcon: true, UPagination: UPaginationStub },
                plugins: [router],
            },
        });

        // initial (page 1)
        expect(wrapper.html()).toContain('A');
        expect(wrapper.html()).toContain('B');
        expect(wrapper.html()).not.toContain('C');

        // Click the page 2 button inside the component's UPagination
        const page2 = wrapper.find('button[value="2"][data-type="page"]');
        await page2.trigger('click');
        await nextTick();

        // page 2 should now be visible
        expect(wrapper.html()).toContain('C');
        expect(wrapper.html()).toContain('D');
        expect(wrapper.html()).not.toContain('A');
    });
});
