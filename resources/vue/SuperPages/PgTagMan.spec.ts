import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import PgTagMan from './PgTagMan.vue';
import { api } from '../AppAxios';
import StdButton from '../Components/StdButton.vue';

// Mock the API module
vi.mock('../AppAxios', () => ({
    api: {
        getTagList: vi.fn(),
    },
}));

describe('PgTagMan.vue', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('mounts and renders the page title and table', () => {
        const wrapper = mount(PgTagMan, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: {
                        template: '<div></div>',
                        methods: {
                            toastDisplay: vi.fn(),
                        },
                    },
                    CmpLayout: { template: '<div><slot /></div>' },
                    CmpCustomTable: true,
                    DialogTagMan: true,
                    UModal: true,
                    UInput: true,
                    UButton: true,
                    UBadge: true,
                },
            },
        });

        expect(wrapper.exists()).toBe(true);
        expect(wrapper.text()).toContain('Tag Management');
    });

    it('fetches tags on mount', async () => {
        const mockTags = [
            {
                id: '1',
                name: 'System',
                description: 'System tag',
                color: '#FF0000',
                enabled: true,
                is_system: true,
                created_at: '2024-01-01',
                updated_at: '2024-01-01',
            },
        ];

        (api.getTagList as any).mockResolvedValueOnce({
            data: { data: mockTags },
        });

        const wrapper = mount(PgTagMan, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: {
                        template: '<div></div>',
                        methods: {
                            toastDisplay: vi.fn(),
                        },
                    },
                    CmpLayout: { template: '<div><slot /></div>' },
                    CmpCustomTable: true,
                    DialogTagMan: true,
                    UModal: true,
                    UInput: true,
                    UButton: true,
                    UBadge: true,
                },
            },
        });

        await wrapper.vm.$nextTick();
        expect(api.getTagList).toHaveBeenCalled();
    });

    it('opens the create dialog when "Create Tag" button is clicked', async () => {
        (api.getTagList as any).mockResolvedValueOnce({
            data: { data: [] },
        });

        const wrapper = mount(PgTagMan, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: {
                        template: '<div></div>',
                        methods: {
                            toastDisplay: vi.fn(),
                        },
                    },
                    CmpLayout: { template: '<div><slot /></div>' },
                    CmpCustomTable: true,
                    DialogTagMan: true,
                    UModal: true,
                    UInput: true,
                    UBadge: true,
                    UButton: {
                        template: '<button @click="$emit(\'click\', $event)"><slot /></button>',
                        props: ['color', 'variant', 'icon', 'class'],
                        emits: ['click'],
                    },
                },
            },
        });

        await wrapper.vm.$nextTick();

        // Find the "Create Tag" button by finding StdButton component
        const createButton = wrapper.findComponent(StdButton);
        expect(createButton.exists()).toBe(true);
        const createButtonProps = createButton.props() as { label?: string };
        expect(createButtonProps.label).toBe('Create Tag');

        // Simulate user clicking the button - need to trigger on the actual button element
        const buttonElement = createButton.find('button');
        await buttonElement.trigger('click');
        await wrapper.vm.$nextTick();

        // Verify dialog state changed (dialogOpen should be true)
        expect((wrapper.vm as any).dialogOpen).toBe(true);
        expect((wrapper.vm as any).dialogHeader).toBe('Create Tag');
        expect((wrapper.vm as any).dialogData).toBeNull();
    });

    it('filters tags when user types in search input', async () => {
        const mockTags = [
            {
                id: '1',
                name: 'Production',
                description: 'Production environment',
                color: '#FF0000',
                enabled: true,
                is_system: false,
                created_at: '2024-01-01',
                updated_at: '2024-01-01',
            },
            {
                id: '2',
                name: 'Development',
                description: 'Development environment',
                color: '#00FF00',
                enabled: true,
                is_system: false,
                created_at: '2024-01-01',
                updated_at: '2024-01-01',
            },
        ];

        (api.getTagList as any).mockResolvedValueOnce({
            data: { data: mockTags },
        });

        const wrapper = mount(PgTagMan, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: {
                        template: '<div></div>',
                        methods: {
                            toastDisplay: vi.fn(),
                        },
                    },
                    CmpLayout: { template: '<div><slot /></div>' },
                    CmpCustomTable: true,
                    DialogTagMan: true,
                    UModal: true,
                    UInput: {
                        template:
                            '<input v-model="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
                        props: ['modelValue'],
                    },
                    UButton: true,
                    UBadge: true,
                },
            },
        });

        await wrapper.vm.$nextTick();

        // Initially, all tags should be visible
        expect((wrapper.vm as any).filteredRows.length).toBe(2);

        // Simulate user typing in search
        const searchInput = wrapper.find('input');
        await searchInput.setValue('Production');
        await wrapper.vm.$nextTick();

        // Only one tag should match
        expect((wrapper.vm as any).filteredRows.length).toBe(1);
        expect((wrapper.vm as any).filteredRows[0].name).toBe('Production');
    });

    it('opens edit dialog with correct data when edit button is clicked', async () => {
        const mockTag = {
            id: '1',
            name: 'Production',
            description: 'Production environment',
            color: '#FF0000',
            enabled: true,
            is_system: false,
            created_at: '2024-01-01',
            updated_at: '2024-01-01',
        };

        (api.getTagList as any).mockResolvedValueOnce({
            data: { data: [mockTag] },
        });

        const wrapper = mount(PgTagMan, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: {
                        template: '<div></div>',
                        methods: {
                            toastDisplay: vi.fn(),
                        },
                    },
                    CmpLayout: { template: '<div><slot /></div>' },
                    CmpCustomTable: true,
                    DialogTagMan: true,
                    UModal: true,
                    UInput: true,
                    UButton: true,
                    UBadge: true,
                },
            },
        });

        await wrapper.vm.$nextTick();

        // Call the openEditTagDialog method directly (simulating user clicking edit)
        (wrapper.vm as any).openEditTagDialog(mockTag);
        await wrapper.vm.$nextTick();

        // Verify dialog opened with correct data
        expect((wrapper.vm as any).dialogOpen).toBe(true);
        expect((wrapper.vm as any).dialogHeader).toBe('Edit Tag');
        expect((wrapper.vm as any).dialogData).toEqual(mockTag);
    });
});
