import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import CmpComponentBuilder from './CmpComponentBuilder.vue';

const globalStubs = {
    Select: { template: '<div />', props: ['modelValue'] },
    InputText: { template: '<input />', props: ['modelValue'] },
    Textarea: { template: '<textarea />', props: ['modelValue'] },
    UButton: { template: '<button />' },
};

describe('CmpComponentBuilder.vue', () => {
    beforeEach(() => {
        vi.resetAllMocks();
    });

    it('emits update:modelValue on mount with empty/default when modelValue is []', async () => {
        const wrapper = mount(CmpComponentBuilder, {
            props: { modelValue: '[]' },
            global: { stubs: globalStubs },
        });

        // initial emit from watch immediate
        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted).toBeTruthy();
        // last emitted value should be '[]' (string)
        const last = emitted![emitted!.length - 1][0];
        expect(last).toBeDefined();
        expect(typeof last).toBe('string');
    });

    it('loads JSON and populates fields from modelValue', async () => {
        const model = [
            { type: 'HEADER', format: 'TEXT', text: 'Hi', example: { header_text: ['Ex'] } },
            { type: 'BODY', text: 'Body text', example: { body_text: [['a', 'b']] } },
            { type: 'FOOTER', text: 'Footer' },
            { type: 'BUTTONS', buttons: [{ type: 'URL', text: 'Go', url: 'https://x' }] },
        ];

        const wrapper = mount(CmpComponentBuilder, {
            props: { modelValue: JSON.stringify(model, null, 2) },
            global: { stubs: globalStubs },
        });

        await wrapper.vm.$nextTick();

        // access internal refs via vm
        expect((wrapper.vm as any).headerText).toBe('Hi');
        expect((wrapper.vm as any).headerExample).toBe('Ex');
        expect((wrapper.vm as any).bodyText).toBe('Body text');
        // bodyExample should join array
        expect((wrapper.vm as any).bodyExample).toContain('a');
        expect((wrapper.vm as any).footerText).toBe('Footer');
        expect((wrapper.vm as any).buttons.length).toBe(1);
    });

    it('addButton and removeButton update jsonOutput and emit updates', async () => {
        const wrapper = mount(CmpComponentBuilder, {
            props: { modelValue: '[]' },
            global: { stubs: globalStubs },
        });

        // add a URL button
        (wrapper.vm as any).addButton('URL');
        await wrapper.vm.$nextTick();

        expect((wrapper.vm as any).buttons.length).toBe(1);

        // update button fields
        (wrapper.vm as any).buttons[0].text = 'Click';
        (wrapper.vm as any).buttons[0].url = 'https://example.com';
        await wrapper.vm.$nextTick();

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted).toBeTruthy();
        const last = emitted![emitted!.length - 1][0];
        expect(typeof last).toBe('string');
        expect(last).toContain('BUTTONS');

        // remove the button
        (wrapper.vm as any).removeButton(0);
        await wrapper.vm.$nextTick();
        expect((wrapper.vm as any).buttons.length).toBe(0);
    });
});
