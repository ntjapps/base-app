import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import CmpMessageDetail from './CmpMessageDetail.vue';
import { api } from '../AppAxios';

import { createPinia } from 'pinia';
import ui from '@nuxt/ui/vue-plugin';
import Echo from 'laravel-echo';
import { setActivePinia } from 'pinia';
import { useEchoStore } from '../AppState';
import CmpToastStub from '../../../tests/mocks/CmpToastStub';

vi.mock('../AppAxios', () => ({
    api: {
        getWhatsappMessagesDetail: vi.fn(),
        postReplyWhatsappMessage: vi.fn(),
    },
}));

beforeEach(() => {
    vi.clearAllMocks();
});
// Neutralize Echo/Pusher used by stores on mount
(globalThis as any).Pusher = vi.fn();
vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);

describe('CmpMessageDetail', () => {
    it('should fetch message history when mounted', async () => {
        const mockPhone = '1234567890';
        const mockResponse = { data: [] };

        (api.getWhatsappMessagesDetail as any).mockResolvedValueOnce(mockResponse);

        // no echo store needed for this test

        const wrapper = mount(CmpMessageDetail, {
            props: {
                dialogOpen: true,
                dialogData: {
                    phone_number: mockPhone,
                },
            },
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: CmpToastStub,
                    Textarea: true,
                    Button: true,
                    RouterLink: true,
                },
            },
        });

        await wrapper.vm.$nextTick();
        expect(api.getWhatsappMessagesDetail).toHaveBeenCalledWith({
            phone_number: mockPhone,
        });
    });

    it('should handle message reply (click send)', async () => {
        const mockPhone = '1234567890';
        const mockResponse = { data: { title: 'Success', message: 'Message sent' } };

        (api.postReplyWhatsappMessage as any).mockResolvedValueOnce(mockResponse);
        (api.getWhatsappMessagesDetail as any).mockResolvedValueOnce({ data: [] });

        const wrapper = mount(CmpMessageDetail, {
            props: {
                dialogOpen: true,
                dialogData: {
                    phone_number: mockPhone,
                },
            },
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: CmpToastStub,
                    Textarea: { template: '<textarea />' },
                    UButton: { name: 'UButton', template: '<button @click="$emit(\'click\')" />' },
                    RouterLink: true,
                },
            },
        });

        // set reply message directly
        (wrapper.vm as any).replyMessage = 'Hello';
        await wrapper.vm.$nextTick();

        // call method directly to avoid UI coupling
        await (wrapper.vm as any).sendReply();

        expect(api.postReplyWhatsappMessage).toHaveBeenCalledWith({
            phone_number: mockPhone,
            message: 'Hello',
        });
    });

    it('should not send when message is empty', async () => {
        const mockPhone = '1234567890';
        (api.postReplyWhatsappMessage as any).mockResolvedValueOnce({ data: {} });

        const wrapper = mount(CmpMessageDetail, {
            props: {
                dialogOpen: true,
                dialogData: {
                    phone_number: mockPhone,
                },
            },
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: CmpToastStub,
                    Textarea: { template: '<textarea />' },
                    UButton: { name: 'UButton', template: '<button @click="$emit(\'click\')" />' },
                    RouterLink: true,
                },
            },
        });

        // ensure empty message prevents API call
        (wrapper.vm as any).replyMessage = '';
        await (wrapper.vm as any).sendReply();

        expect(api.postReplyWhatsappMessage).not.toHaveBeenCalled();
    });

    it('should refresh silently when WhatsappMessageReceived event is broadcast and dialog is open', async () => {
        const mockPhone = '1234567890';
        const mockResponse = { data: [] };

        (api.getWhatsappMessagesDetail as any).mockResolvedValue(mockResponse);

        // Set up Pinia/Echo store
        const pinia = createPinia();
        setActivePinia(pinia);
        (globalThis as any).Pusher = vi.fn();
        vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);
        // Inject a fake echo object into echo store that captures the callback
        const echoStore = useEchoStore();
        const fakeChannel: any = {};
        const capturedCallbacks = new Map<string, (...args: any[]) => void>();
        fakeChannel.listen = vi.fn((event: string, cb: (...args: any[]) => void) => {
            capturedCallbacks.set(event, cb);
        });
        // Ensure laravelEcho is set before mount so the component subscribes to our fake channel
        echoStore.laravelEcho = { private: () => fakeChannel } as unknown as any;

        const wrapper = mount(CmpMessageDetail, {
            props: {
                dialogOpen: true,
                dialogData: {
                    phone_number: mockPhone,
                },
            },
            global: {
                plugins: [pinia, ui],
                stubs: {
                    CmpToast: CmpToastStub,
                    Textarea: true,
                    Button: true,
                    RouterLink: true,
                },
            },
        });

        await wrapper.vm.$nextTick();

        expect(api.getWhatsappMessagesDetail).toHaveBeenCalledTimes(1);

        // We will inject fake echo object into echo store before mount in the test below

        // Simulate the WhatsappMessageReceived broadcast
        // If subscribe call happened, callback should be set; else throw for test clarity
        const receivedCb = capturedCallbacks.get('WhatsappMessageReceived');
        expect(receivedCb).toBeDefined();
        if (receivedCb) {
            receivedCb();
        }

        // Wait a tick so any reactive updates occur
        await wrapper.vm.$nextTick();

        // By now, the API should have been called again to refresh silently
        expect(api.getWhatsappMessagesDetail).toHaveBeenCalledTimes(2);
    });

    it('should refresh silently when WhatsappMessageSent event is broadcast and dialog is open', async () => {
        const mockPhone = '1234567890';
        const mockResponse = { data: [] };

        (api.getWhatsappMessagesDetail as any).mockResolvedValue(mockResponse);

        // Set up Pinia/Echo store
        const pinia = createPinia();
        setActivePinia(pinia);
        (globalThis as any).Pusher = vi.fn();
        vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);
        // Inject a fake echo object into echo store that captures the callback
        const echoStore = useEchoStore();
        const fakeChannel: any = {};
        const capturedCallbacks = new Map<string, (...args: any[]) => void>();
        fakeChannel.listen = vi.fn((event: string, cb: (...args: any[]) => void) => {
            capturedCallbacks.set(event, cb);
        });
        // Ensure laravelEcho is set before mount so the component subscribes to our fake channel
        echoStore.laravelEcho = { private: () => fakeChannel } as unknown as any;

        const wrapper = mount(CmpMessageDetail, {
            props: {
                dialogOpen: true,
                dialogData: {
                    phone_number: mockPhone,
                },
            },
            global: {
                plugins: [pinia, ui],
                stubs: {
                    CmpToast: CmpToastStub,
                    Textarea: true,
                    Button: true,
                    RouterLink: true,
                },
            },
        });

        await wrapper.vm.$nextTick();

        expect(api.getWhatsappMessagesDetail).toHaveBeenCalledTimes(1);

        // Simulate the WhatsappMessageSent broadcast
        const sentCb = capturedCallbacks.get('WhatsappMessageSent');
        expect(sentCb).toBeDefined();
        if (sentCb) {
            sentCb();
        }

        // Wait a tick so any reactive updates occur
        await wrapper.vm.$nextTick();

        // By now, the API should have been called again to refresh silently
        expect(api.getWhatsappMessagesDetail).toHaveBeenCalledTimes(2);
    });

    it('should update message list when WhatsappMessageReceived payload belongs to the dialog', async () => {
        const mockPhone = '1234567890';
        (api.getWhatsappMessagesDetail as any).mockResolvedValue({ data: [] });

        // Set up Pinia/Echo store
        const pinia = createPinia();
        setActivePinia(pinia);
        (globalThis as any).Pusher = vi.fn();
        vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);
        const echoStore = useEchoStore();
        const fakeChannel: any = {};
        const capturedCallbacks = new Map<string, (...args: any[]) => void>();
        fakeChannel.listen = vi.fn((event: string, cb: (...args: any[]) => void) => {
            capturedCallbacks.set(event, cb);
        });
        echoStore.laravelEcho = { private: () => fakeChannel } as unknown as any;

        const wrapper = mount(CmpMessageDetail, {
            props: {
                dialogOpen: true,
                dialogData: {
                    phone_number: mockPhone,
                },
            },
            global: {
                plugins: [pinia, ui],
                stubs: { CmpToast: CmpToastStub, Textarea: true, Button: true, RouterLink: true },
            },
        });

        await wrapper.vm.$nextTick();
        expect(api.getWhatsappMessagesDetail).toHaveBeenCalledTimes(1);

        // Simulate a payload directly (no full refresh)
        const payload = {
            webhook_log: {
                id: 'w1',
                from: mockPhone,
                body: 'Hello from user',
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

        const receivedCb = capturedCallbacks.get('WhatsappMessageReceived');
        expect(receivedCb).toBeDefined();
        if (receivedCb) {
            receivedCb(payload);
        }

        await wrapper.vm.$nextTick();

        // Should not have called API again; local update only
        expect(api.getWhatsappMessagesDetail).toHaveBeenCalledTimes(1);
        expect((wrapper.vm as any).messageDetail.length).toBe(1);
        expect((wrapper.vm as any).messageDetail[0].messageable_id).toBe('w1');
        expect((wrapper.vm as any).messageDetail[0].messageable.message_body).toBe(
            'Hello from user',
        );
    });

    it('should update message list when WhatsappMessageSent payload belongs to the dialog', async () => {
        const mockPhone = '1234567890';
        (api.getWhatsappMessagesDetail as any).mockResolvedValue({ data: [] });

        // Set up Pinia/Echo store
        const pinia = createPinia();
        setActivePinia(pinia);
        (globalThis as any).Pusher = vi.fn();
        vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);
        const echoStore = useEchoStore();
        const fakeChannel: any = {};
        const capturedCallbacks = new Map<string, (...args: any[]) => void>();
        fakeChannel.listen = vi.fn((event: string, cb: (...args: any[]) => void) => {
            capturedCallbacks.set(event, cb);
        });
        echoStore.laravelEcho = { private: () => fakeChannel } as unknown as any;

        const wrapper = mount(CmpMessageDetail, {
            props: {
                dialogOpen: true,
                dialogData: {
                    phone_number: mockPhone,
                },
            },
            global: {
                plugins: [pinia, ui],
                stubs: { CmpToast: CmpToastStub, Textarea: true, Button: true, RouterLink: true },
            },
        });

        await wrapper.vm.$nextTick();
        expect(api.getWhatsappMessagesDetail).toHaveBeenCalledTimes(1);

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

        const sentCb = capturedCallbacks.get('WhatsappMessageSent');
        expect(sentCb).toBeDefined();
        if (sentCb) {
            sentCb(payload);
        }

        await wrapper.vm.$nextTick();

        expect(api.getWhatsappMessagesDetail).toHaveBeenCalledTimes(1);
        expect((wrapper.vm as any).messageDetail.length).toBe(1);
        expect((wrapper.vm as any).messageDetail[0].messageable_id).toBe('s1');
        expect((wrapper.vm as any).messageDetail[0].messageable.message_content).toBe(
            'Reply from agent',
        );
    });
});
