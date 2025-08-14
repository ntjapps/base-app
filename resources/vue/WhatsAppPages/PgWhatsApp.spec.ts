import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import PgWhatsApp from './PgWhatsApp.vue';
import { AppAxios } from '../AppAxios';
import PrimeVue from 'primevue/config';
import Echo from 'laravel-echo';

vi.mock('../AppAxios', () => ({
    AppAxios: {
        getWhatsappMessagesList: vi.fn(),
        getWaThreadsList: vi.fn(),
    },
}));

beforeEach(() => {
    setActivePinia(createPinia());
    // Neutralize Echo/Pusher used by stores on mount
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (globalThis as any).Pusher = vi.fn();
    vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);
});

describe('PgWhatsApp', () => {
    it('should fetch thread list when mounted', async () => {
    const mockResponse = { data: [] };

    (AppAxios.getWaThreadsList as any).mockResolvedValueOnce(mockResponse);

        const wrapper = mount(PgWhatsApp, {
            props: {
                appName: 'Test App',
                greetings: 'Hello',
                expandedKeysProps: ''
            },
            global: {
                plugins: [PrimeVue],
                stubs: {
                    TooltipRoot: true,
                    ThreadList: true,
                    CmpMessageDetail: true,
                    RouterLink: true,
                    DataTable: true,
                    Column: true,
                    InputText: true,
                    UButton: true,
                },
                mocks: {
                    $route: {},
                    $router: { push: vi.fn() },
                },
            }
        });

        await wrapper.vm.$nextTick();

    expect(AppAxios.getWaThreadsList).toHaveBeenCalled();
    });

    it('should update thread list data after fetch', async () => {
        const mockThreads = [
            {
                id: '1',
                phone_number: '1234567890',
                last_message_at: '2023-01-01',
                message_preview: 'Test message'
            }
        ];

    const mockResponse = { data: mockThreads };

    (AppAxios.getWaThreadsList as any).mockResolvedValueOnce(mockResponse);

        const wrapper = mount(PgWhatsApp, {
            props: {
                appName: 'Test App',
                greetings: 'Hello',
                expandedKeysProps: ''
            },
            global: {
                plugins: [PrimeVue],
                stubs: {
                    TooltipRoot: true,
                    ThreadList: true,
                    CmpMessageDetail: true,
                    RouterLink: true,
                    DataTable: true,
                    Column: true,
                    InputText: true,
                    UButton: true,
                },
                mocks: {
                    $route: {},
                    $router: { push: vi.fn() },
                },
            }
        });

        await wrapper.vm.$nextTick();

    // Since the component uses DataTable with :value binding, validate reactive data
    // by checking internal state via vm
    expect((wrapper.vm as any).threadListData).toEqual(mockThreads);
    });
});
