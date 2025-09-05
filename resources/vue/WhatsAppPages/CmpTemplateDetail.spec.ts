import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import CmpTemplateDetail from './CmpTemplateDetail.vue';

// Mock AppAxios api
vi.mock('../AppAxios', () => {
    return {
        api: {
            postCreateWhatsappTemplate: vi.fn(() => Promise.resolve({ data: { success: true } })),
            postUpdateWhatsappTemplate: vi.fn(() => Promise.resolve({ data: { success: true } })),
        },
    };
});

// Stub child components used inside the component to keep tests focused
const globalStubs = {
    Select: { template: '<div />' },
    InputText: { template: '<input />' },
    Textarea: { template: '<textarea />' },
    CmpComponentBuilder: {
        template: '<div />',
        props: ['modelValue'],
        emits: ['update:modelValue'],
    },
    UCheckbox: {
        template: '<input type="checkbox" />',
        props: ['modelValue'],
        emits: ['update:modelValue'],
    },
    UButton: { template: '<button />', props: ['loading'] },
};

describe('CmpTemplateDetail.vue', () => {
    beforeEach(() => {
        vi.resetAllMocks();
    });

    it('prefills form when dialogOpen and dialogData with id provided', async () => {
        const wrapper = mount(CmpTemplateDetail, {
            props: {
                dialogOpen: true,
                dialogData: {
                    id: 'tpl-1',
                    name: 'My Template',
                    language: 'es_ES',
                    category: 'MARKETING',
                    components: [{ type: 'BODY', text: 'hello' }],
                    message_send_ttl_seconds: 3600,
                    cta_url_link_tracking_opted_out: true,
                },
                mode: 'edit',
            },
            global: { stubs: globalStubs },
        });

        // wait for nextTick / mounted
        await wrapper.vm.$nextTick();

        // check that fields were prefilled
        expect((wrapper.vm as any).name).toBe('My Template');
        expect((wrapper.vm as any).language).toBe('es_ES');
        expect((wrapper.vm as any).category).toBe('MARKETING');
        expect((wrapper.vm as any).components).toContain('BODY');
        expect((wrapper.vm as any).messageSendTtlSeconds).toBe(3600);
        expect((wrapper.vm as any).ctaUrlLinkTrackingOptedOut).toBe(true);
    });

    it('calls create API on save when mode is create', async () => {
        const { api } = await import('../AppAxios');
        const wrapper = mount(CmpTemplateDetail, {
            props: {
                dialogOpen: true,
                dialogData: null,
                mode: 'create',
            },
            global: { stubs: globalStubs },
        });

        // set some fields
        (wrapper.vm as any).name = 'New';
        (wrapper.vm as any).language = 'en_US';
        (wrapper.vm as any).category = 'UTILITY';
        (wrapper.vm as any).components = JSON.stringify([], null, 2);

        await (wrapper.vm as any).save();

        expect(api.postCreateWhatsappTemplate).toHaveBeenCalled();
    });

    it('calls update API on save when mode is edit with id', async () => {
        const { api } = await import('../AppAxios');
        const wrapper = mount(CmpTemplateDetail, {
            props: {
                dialogOpen: true,
                dialogData: { id: 'tpl-2' },
                mode: 'edit',
            },
            global: { stubs: globalStubs },
        });

        (wrapper.vm as any).name = 'Edit';
        (wrapper.vm as any).components = JSON.stringify([], null, 2);

        await (wrapper.vm as any).save();

        expect(api.postUpdateWhatsappTemplate).toHaveBeenCalledWith('tpl-2', expect.any(Object));
    });

    it('emits closeDialog and updates dialogOpen when close called', async () => {
        const wrapper = mount(CmpTemplateDetail, {
            props: {
                dialogOpen: true,
                dialogData: null,
                mode: 'create',
            },
            global: { stubs: globalStubs },
        });

        (wrapper.vm as any).close();

        // emitted closeDialog
        expect(wrapper.emitted()).toHaveProperty('closeDialog');
        // update:dialogOpen should be emitted with false
        expect(wrapper.emitted('update:dialogOpen')?.[0]).toEqual([false]);
    });
});
