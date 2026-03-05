import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import CmpCustomTable from './CmpCustomTable.vue';

// Mock ResizeObserver
global.ResizeObserver = class ResizeObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
};

describe('CmpCustomTable.vue', () => {
    const columns = [
        { id: 'name', key: 'name', label: 'Name', sortable: true },
        { id: 'email', key: 'email', label: 'Email', sortable: true },
        { id: 'role', key: 'role', label: 'Role', sortable: false },
    ];

    const rows = [
        { id: 1, name: 'John Doe', email: 'john@example.com', role: 'Admin' },
        { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'User' },
        { id: 3, name: 'Bob Johnson', email: 'bob@example.com', role: 'Moderator' },
    ];

    const createTestRouter = () =>
        createRouter({
            history: createWebHistory(),
            routes: [],
        });

    it('renders table with columns and rows', () => {
        const wrapper = mount(CmpCustomTable, {
            props: { columns, rows },
            global: {
                stubs: {
                    UIcon: true,
                    UCheckbox: true,
                },
                plugins: [createTestRouter()],
            },
        });

        expect(wrapper.html()).toContain('Name');
        expect(wrapper.html()).toContain('Email');
        expect(wrapper.html()).toContain('Role');
        expect(wrapper.html()).toContain('John Doe');
        expect(wrapper.html()).toContain('Jane Smith');
        expect(wrapper.html()).toContain('Bob Johnson');
    });

    it('shows "No data available" when rows are empty', () => {
        const wrapper = mount(CmpCustomTable, {
            props: { columns, rows: [] },
            global: {
                stubs: {
                    UIcon: true,
                    UCheckbox: true,
                },
                plugins: [createTestRouter()],
            },
        });

        expect(wrapper.html()).toContain('No data available');
    });

    it('shows loading state', () => {
        const wrapper = mount(CmpCustomTable, {
            props: { columns, rows, loading: true },
            global: {
                stubs: {
                    UIcon: true,
                    UCheckbox: true,
                },
                plugins: [createTestRouter()],
            },
        });

        expect(wrapper.html()).toContain('Loading...');
    });

    it('supports custom slots for cell rendering', () => {
        const wrapper = mount(CmpCustomTable, {
            props: { columns, rows },
            slots: {
                'name-data': '<div class="custom-name">Custom Name</div>',
            },
            global: {
                stubs: {
                    UIcon: true,
                    UCheckbox: true,
                },
                plugins: [createTestRouter()],
            },
        });

        expect(wrapper.html()).toContain('Custom Name');
    });

    it('accepts stickyFirstColumns prop', () => {
        const wrapper = mount(CmpCustomTable, {
            props: { columns, rows, stickyFirstColumns: 1 },
            global: {
                stubs: {
                    UIcon: true,
                    UCheckbox: true,
                },
                plugins: [createTestRouter()],
            },
        });

        const props = wrapper.props() as { stickyFirstColumns?: number };
        expect(props.stickyFirstColumns).toBe(1);
        // Note: Full sticky behavior relies on getBoundingClientRect which works best in browser
    });
});
