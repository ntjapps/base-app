import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import ui from '@nuxt/ui/vue-plugin';
import DialogAiModelInstructionMan from './DialogAiModelInstructionMan.vue';
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
            postAiModelInstructionManSubmit: vi.fn(() =>
                Promise.resolve({
                    data: { title: 'Instruction saved', message: 'Instruction saved' },
                }),
            ),
            postDeleteAiModelInstructionManSubmit: vi.fn(() =>
                Promise.resolve({
                    data: { title: 'Instruction deleted', message: 'Instruction deleted' },
                }),
            ),
        },
    };
});

describe('DialogAiModelInstructionMan.vue', () => {
    beforeEach(() => {
        (api.postAiModelInstructionManSubmit as any).mockClear();
        (api.postDeleteAiModelInstructionManSubmit as any).mockClear();
    });

    it('loads initial props and submits enabled value', async () => {
        const dialogData = {
            id: 'inst-1',
            name: 'Test',
            key: 'test_key',
            instructions: 'Do stuff',
            enabled: false,
            scope: null,
        };

        const wrapper = mount(DialogAiModelInstructionMan as any, {
            props: {
                dialogOpen: true,
                dialogData: dialogData,
                dialogTypeCreate: false,
            },
            global: {
                plugins: [ui],
                stubs: {
                    USwitch: USwitchStub,
                    UButton: UButtonStub,
                },
            },
        });

        await nextTick();

        const switchBtn = wrapper.find('button[role="switch"]');
        expect(switchBtn.exists()).toBe(true);
        expect(switchBtn.attributes('aria-checked')).toBe('false');
        await switchBtn.trigger('click');
        expect(switchBtn.attributes('aria-checked')).toBe('true');

        const buttons = wrapper.findAll('button');
        const submitBtn = buttons.find((b) => b.text().includes('Submit')) ?? buttons[0];
        expect(submitBtn.exists()).toBe(true);
        await submitBtn.trigger('click');

        expect(api.postAiModelInstructionManSubmit).toHaveBeenCalled();
        const [payload] = (api.postAiModelInstructionManSubmit as any).mock.calls[0];
        expect(payload.enabled).toBe(true);
    });

    it('sets default enabled true on create and can toggle before submit', async () => {
        const wrapper = mount(DialogAiModelInstructionMan as any, {
            props: {
                dialogOpen: true,
                dialogData: null,
                dialogTypeCreate: true,
            },
            global: {
                plugins: [ui],
                stubs: {
                    USwitch: USwitchStub,
                    UButton: UButtonStub,
                },
            },
        });

        await nextTick();

        const switchBtn = wrapper.find('button[role="switch"]');
        expect(switchBtn.exists()).toBe(true);
        expect(switchBtn.attributes('aria-checked')).toBe('true');
        await switchBtn.trigger('click');
        expect(switchBtn.attributes('aria-checked')).toBe('false');

        const buttons = wrapper.findAll('button');
        const submitBtn = buttons.find((b) => b.text().includes('Submit')) ?? buttons[0];
        await submitBtn.trigger('click');

        expect(api.postAiModelInstructionManSubmit).toHaveBeenCalled();
        const [payload] = (api.postAiModelInstructionManSubmit as any).mock.calls[0];
        expect(payload.enabled).toBe(false);
    });
});
