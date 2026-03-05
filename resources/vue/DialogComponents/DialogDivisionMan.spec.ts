import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import ui from '@nuxt/ui/vue-plugin';
import DialogDivisionMan from './DialogDivisionMan.vue';
import { api } from '../AppAxios';
import { nextTick } from 'vue';

const USwitchStub = {
    template:
        '<button role="switch" :aria-checked="modelValue ? \'true\' : \'false\'" @click="$emit(\'update:modelValue\', !modelValue)"></button>',
    props: ['modelValue'],
    emits: ['update:modelValue'],
};

const UButtonStub = {
    template: '<button @click="$emit(\'click\')"><slot /></button>',
    emits: ['click'],
};

vi.mock('../AppAxios', () => {
    return {
        api: {
            postDivisionManSubmit: vi.fn(() =>
                Promise.resolve({ data: { title: 'Division saved', message: 'Division saved' } }),
            ),
            postDeleteDivisionManSubmit: vi.fn(() =>
                Promise.resolve({
                    data: { title: 'Division deleted', message: 'Division deleted' },
                }),
            ),
        },
    };
});

describe('DialogDivisionMan.vue', () => {
    beforeEach(() => {
        (api.postDivisionManSubmit as any).mockClear();
        (api.postDeleteDivisionManSubmit as any).mockClear();
    });

    it('loads initial props and submits enabled value', async () => {
        const dialogData = {
            id: 'div-1',
            name: 'Billing',
            description: 'Billing division',
            enabled: false,
        };

        const wrapper = mount(DialogDivisionMan as any, {
            props: {
                dialogOpen: true,
                dialogData: dialogData,
                dialogTypeCreate: false,
            },
            global: {
                plugins: [ui],
                stubs: {
                    InputText: true,
                    Textarea: true,
                    USwitch: USwitchStub,
                    UButton: UButtonStub,
                },
            },
        });

        await nextTick();

        // USwitch is present as a button-based switch; find by role
        const switchBtn = wrapper.find('button[role="switch"]');
        expect(switchBtn.exists()).toBe(true);
        // initial should be false as passed in dialogData
        expect(switchBtn.attributes('aria-checked')).toBe('false');
        await switchBtn.trigger('click');
        expect(switchBtn.attributes('aria-checked')).toBe('true');

        // Submit
        // Find submit button by text and click
        const buttons = wrapper.findAll('button');
        const submitBtn = buttons.find((b) => b.text().includes('Submit')) ?? buttons[0];
        expect(submitBtn.exists()).toBe(true);
        await submitBtn.trigger('click');

        expect(api.postDivisionManSubmit).toHaveBeenCalled();
        const [payload] = (api.postDivisionManSubmit as any).mock.calls[0];
        expect(payload.enabled).toBe(true);
    });

    it('sets default enabled true on create and can toggle before submit', async () => {
        const wrapper = mount(DialogDivisionMan as any, {
            props: {
                dialogOpen: true,
                dialogData: null,
                dialogTypeCreate: true,
            },
            global: {
                plugins: [ui],
                stubs: {
                    InputText: true,
                    Textarea: true,
                    USwitch: USwitchStub,
                    UButton: UButtonStub,
                },
            },
        });

        await nextTick();

        // initial value should be true by default for create
        const switchBtn = wrapper.find('button[role="switch"]');
        expect(switchBtn.exists()).toBe(true);
        // create modal should default to true
        expect(switchBtn.attributes('aria-checked')).toBe('true');
        // toggle to false
        await switchBtn.trigger('click');
        expect(switchBtn.attributes('aria-checked')).toBe('false');

        // Submit
        const buttons = wrapper.findAll('button');
        const submitBtn = buttons.find((b) => b.text().includes('Submit')) ?? buttons[0];
        await submitBtn.trigger('click');

        expect(api.postDivisionManSubmit).toHaveBeenCalled();
        const [payload] = (api.postDivisionManSubmit as any).mock.calls[0];
        expect(payload.enabled).toBe(false);
    });
});
