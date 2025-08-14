import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import CmpMessageDetail from './CmpMessageDetail.vue';
import { AppAxios } from '../AppAxios';
import PrimeVue from 'primevue/config';

vi.mock('../AppAxios', () => ({
    AppAxios: {
        getWhatsappMessagesDetail: vi.fn(),
        getWaThreadDetail: vi.fn(),
        postReplyWhatsappMessage: vi.fn(),
    },
}));

beforeEach(() => {
    vi.clearAllMocks();
});

describe('CmpMessageDetail', () => {
    it('should fetch message history when mounted', async () => {
        const mockPhone = '1234567890';
    const mockResponse = { data: [] };

    (AppAxios.getWaThreadDetail as any).mockResolvedValueOnce(mockResponse);

        const wrapper = mount(CmpMessageDetail, {
            props: {
                dialogOpen: true,
                dialogData: {
                    phone_number: mockPhone
                }
            },
            global: {
                plugins: [PrimeVue],
                stubs: {
                    Textarea: true,
                    Button: true,
                    RouterLink: true,
                }
            }
        });

        await wrapper.vm.$nextTick();
        expect(AppAxios.getWaThreadDetail).toHaveBeenCalledWith({
            phone_number: mockPhone,
        });
    });

    it('should handle message reply (click send)', async () => {
        const mockPhone = '1234567890';
    const mockResponse = { data: { title: 'Success', message: 'Message sent' } };

    (AppAxios.postReplyWhatsappMessage as any).mockResolvedValueOnce(mockResponse);
    (AppAxios.getWaThreadDetail as any).mockResolvedValueOnce({ data: [] });

        const wrapper = mount(CmpMessageDetail, {
            props: {
                dialogOpen: true,
                dialogData: {
                    phone_number: mockPhone,
                },
            },
            global: {
                plugins: [PrimeVue],
                stubs: {
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

    expect(AppAxios.postReplyWhatsappMessage).toHaveBeenCalledWith({
            phone_number: mockPhone,
            message: 'Hello',
        });
    });

    it('should not send when message is empty', async () => {
        const mockPhone = '1234567890';
        (AppAxios.postReplyWhatsappMessage as any).mockResolvedValueOnce({ data: {} });

        const wrapper = mount(CmpMessageDetail, {
            props: {
                dialogOpen: true,
                dialogData: {
                    phone_number: mockPhone
                }
        },
            global: {
                plugins: [PrimeVue],
                stubs: {
            Textarea: { template: '<textarea />' },
            UButton: { name: 'UButton', template: '<button @click="$emit(\'click\')" />' },
            RouterLink: true,
                }
            }
        });

        // ensure empty message prevents API call
        (wrapper.vm as any).replyMessage = '';
        await (wrapper.vm as any).sendReply();

        expect(AppAxios.postReplyWhatsappMessage).not.toHaveBeenCalled();
    });
});
