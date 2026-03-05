import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import PgWhatsApp from './PgWhatsApp.vue';
import { api } from '../AppAxios';
import Echo from 'laravel-echo';
import { useEchoStore } from '../AppState';
import CmpToastStub from '../../../tests/mocks/CmpToastStub';

vi.mock('../AppAxios', () => ({
    api: {
        getWhatsappMessagesList: vi.fn(),
        getWhatsappStats: vi.fn(),
    },
}));

beforeEach(() => {
    setActivePinia(createPinia());
    // Neutralize Echo/Pusher used by stores on mount

    (globalThis as any).Pusher = vi.fn();
    vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);
});

describe('PgWhatsApp', () => {
    it('should fetch thread list when mounted', async () => {
        const mockResponse = { data: [] };

        (api.getWhatsappMessagesList as any).mockResolvedValueOnce(mockResponse);

        const wrapper = mount(PgWhatsApp, {
            global: {
                stubs: {
                    CmpToast: CmpToastStub,
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
            },
        });

        await wrapper.vm.$nextTick();

        expect(api.getWhatsappMessagesList).toHaveBeenCalled();
    });

    it('should update thread list data after fetch', async () => {
        const mockThreads = [
            {
                id: '1',
                phone_number: '1234567890',
                last_message_at: '2023-01-01',
                message_preview: 'Test message',
            },
        ];

        const mockResponse = { data: mockThreads };

        (api.getWhatsappMessagesList as any).mockResolvedValueOnce(mockResponse);

        const wrapper = mount(PgWhatsApp, {
            global: {
                stubs: {
                    CmpToast: CmpToastStub,
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
            },
        });

        await wrapper.vm.$nextTick();

        // Since the component uses DataTable with :value binding, validate reactive data
        // by checking internal state via vm
        expect((wrapper.vm as any).threadListData).toEqual(mockThreads);
    });

    it('should insert new thread on WhatsappMessageReceived payload', async () => {
        const mockPhone = '1234567890';
        (api.getWhatsappMessagesList as any).mockResolvedValue({ data: [] });
        (api.getWhatsappStats as any).mockResolvedValue({
            data: { total: 0, open: 0, pending: 0, resolved: 0 },
        });

        const pinia = createPinia();
        setActivePinia(pinia);
        (globalThis as any).Pusher = vi.fn();
        vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);

        const echoStore = useEchoStore();
        const fakeChannel: { listen: (event: string, cb: (...args: any[]) => void) => void } = {
            listen: () => undefined,
        };
        const capturedCb = { current: undefined as undefined | ((...args: any[]) => void) };
        fakeChannel.listen = (_event, cb) => {
            capturedCb.current = cb;
        };
        echoStore.laravelEcho = { private: () => fakeChannel } as unknown as any;

        const wrapper = mount(PgWhatsApp, {
            global: {
                plugins: [pinia],
                stubs: {
                    CmpToast: CmpToastStub,
                },
            },
        });

        await wrapper.vm.$nextTick();
        expect(api.getWhatsappMessagesList).toHaveBeenCalled();

        const payload = {
            webhook_log: {
                id: 'w1',
                from: mockPhone,
                body: 'Hello inbound',
                message_id: 'm1',
                created_at: new Date().toISOString(),
            },
            thread: {
                id: 't1',
                phone_number: mockPhone,
                last_message_at: new Date().toISOString(),
                status: 'OPEN',
            },
        };

        expect(capturedCb.current).toBeDefined();
        capturedCb.current?.(payload);
        await wrapper.vm.$nextTick();

        expect((wrapper.vm as any).threadListData.length).toBe(1);
        expect((wrapper.vm as any).threadListData[0].phone_number).toBe(mockPhone);
        expect((wrapper.vm as any).threadListData[0].message_preview).toBe('Hello inbound');
    });

    it('should update existing thread on WhatsappMessageSent payload', async () => {
        const mockPhone = '1234567890';
        (api.getWhatsappMessagesList as any).mockResolvedValue({
            data: [
                {
                    id: 't1',
                    phone_number: mockPhone,
                    message_preview: 'old',
                    last_message_at: null,
                    status: 'OPEN',
                    contact_name: null,
                    assigned_agent: null,
                    needs_reply: false,
                },
            ],
        });
        (api.getWhatsappStats as any).mockResolvedValue({
            data: { total: 1, open: 1, pending: 0, resolved: 0 },
        });

        const pinia = createPinia();
        setActivePinia(pinia);
        (globalThis as any).Pusher = vi.fn();
        vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);

        const echoStore = useEchoStore();
        const fakeChannel: { listen: (event: string, cb: (...args: any[]) => void) => void } = {
            listen: () => undefined,
        };
        const capturedCb = { current: undefined as undefined | ((...args: any[]) => void) };
        fakeChannel.listen = (_event, cb) => {
            capturedCb.current = cb;
        };
        echoStore.laravelEcho = { private: () => fakeChannel } as unknown as any;

        const wrapper = mount(PgWhatsApp, {
            global: {
                plugins: [pinia],
                stubs: {
                    CmpToast: CmpToastStub,
                },
            },
        });

        await wrapper.vm.$nextTick();
        expect(api.getWhatsappMessagesList).toHaveBeenCalled();
        expect((wrapper.vm as any).threadListData.length).toBe(1);
        expect((wrapper.vm as any).threadListData[0].message_preview).toBe('old');

        const payload = {
            sent_log: {
                id: 's1',
                recipient_number: mockPhone,
                message: 'Reply from agent',
                created_at: new Date().toISOString(),
            },
            thread: {
                id: 't1',
                phone_number: mockPhone,
                last_message_at: new Date().toISOString(),
                status: 'OPEN',
            },
        };

        expect(capturedCb.current).toBeDefined();
        capturedCb.current?.(payload);
        await wrapper.vm.$nextTick();

        expect((wrapper.vm as any).threadListData.length).toBe(1);
        expect((wrapper.vm as any).threadListData[0].message_preview).toBe('Reply from agent');
    });

    it('should fallback to refresh when payload is missing', async () => {
        (api.getWhatsappMessagesList as any).mockResolvedValue({ data: [] });
        (api.getWhatsappStats as any).mockResolvedValue({
            data: { total: 0, open: 0, pending: 0, resolved: 0 },
        });

        const pinia = createPinia();
        setActivePinia(pinia);
        (globalThis as any).Pusher = vi.fn();
        vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);

        const echoStore = useEchoStore();
        const fakeChannel: { listen: (event: string, cb: (...args: any[]) => void) => void } = {
            listen: () => undefined,
        };
        const capturedCb = { current: undefined as undefined | ((...args: any[]) => void) };
        fakeChannel.listen = (_event, cb) => {
            capturedCb.current = cb;
        };
        echoStore.laravelEcho = { private: () => fakeChannel } as unknown as any;

        const wrapper = mount(PgWhatsApp, {
            global: {
                plugins: [pinia],
                stubs: {
                    CmpToast: CmpToastStub,
                },
            },
        });

        await wrapper.vm.$nextTick();
        expect(api.getWhatsappMessagesList).toHaveBeenCalled();

        expect(capturedCb.current).toBeDefined();
        const beforeCount = (api.getWhatsappMessagesList as any).mock.calls.length;
        capturedCb.current?.();

        await wrapper.vm.$nextTick();

        // Should have called refresh (getThreadListData true), so getWhatsappMessagesList called again
        expect((api.getWhatsappMessagesList as any).mock.calls.length).toBe(beforeCount + 1);
    });
});
