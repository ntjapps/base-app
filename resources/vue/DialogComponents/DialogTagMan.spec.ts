import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import ui from '@nuxt/ui/vue-plugin';
import DialogTagMan from './DialogTagMan.vue';
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
            postTagManSubmit: vi.fn(() =>
                Promise.resolve({ data: { title: 'Tag saved', message: 'Tag saved' } }),
            ),
            postDeleteTagManSubmit: vi.fn(() =>
                Promise.resolve({ data: { title: 'Tag deleted', message: 'Tag deleted' } }),
            ),
        },
    };
});

describe('DialogTagMan.vue', () => {
    beforeEach(() => {
        (api.postTagManSubmit as any).mockClear();
        (api.postDeleteTagManSubmit as any).mockClear();
    });

    it('loads initial props and submits enabled value', async () => {
        const dialogData = {
            id: 'tag-1',
            name: 'Important',
            description: 'Important messages',
            color: '#ff0000',
            enabled: false,
            is_system: false,
        };

        const wrapper = mount(DialogTagMan as any, {
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

        const switchBtn = wrapper.find('button[role="switch"]');
        expect(switchBtn.exists()).toBe(true);
        expect(switchBtn.attributes('aria-checked')).toBe('false');
        await switchBtn.trigger('click');
        expect(switchBtn.attributes('aria-checked')).toBe('true');

        const buttons = wrapper.findAll('button');
        const submitBtn = buttons.find((b) => b.text().includes('Submit')) ?? buttons[0];
        expect(submitBtn.exists()).toBe(true);
        await submitBtn.trigger('click');

        expect(api.postTagManSubmit).toHaveBeenCalled();
        const [payload] = (api.postTagManSubmit as any).mock.calls[0];
        expect(payload.enabled).toBe(true);
    });

    it('create modal defaults to enabled true and can toggle then submit', async () => {
        const wrapper = mount(DialogTagMan as any, {
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

        const switchBtn = wrapper.find('button[role="switch"]');
        expect(switchBtn.exists()).toBe(true);
        expect(switchBtn.attributes('aria-checked')).toBe('true');
        await switchBtn.trigger('click');
        expect(switchBtn.attributes('aria-checked')).toBe('false');

        const buttons = wrapper.findAll('button');
        const submitBtn = buttons.find((b) => b.text().includes('Submit')) ?? buttons[0];
        await submitBtn.trigger('click');

        expect(api.postTagManSubmit).toHaveBeenCalled();
        const [payload] = (api.postTagManSubmit as any).mock.calls[0];
        expect(payload.enabled).toBe(false);
    });

    it('disables fields when is_system is true', async () => {
        const dialogData = {
            id: 'tag-1',
            name: 'SystemTag',
            description: 'System',
            color: '#000000',
            enabled: true,
            is_system: true,
        };

        const wrapper = mount(DialogTagMan as any, {
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

        const switchBtn = wrapper.find('button[role="switch"]');
        expect(switchBtn.exists()).toBe(true);
        // clicking the switch shouldn't toggle it when it's a system tag
        const initialChecked = switchBtn.attributes('aria-checked');
        await switchBtn.trigger('click');
        expect(switchBtn.attributes('aria-checked')).toBe(initialChecked);

        // color input should be disabled when is_system is true
        const colorInput = wrapper.find('input[type="color"]');
        expect(colorInput.exists()).toBe(true);
        expect(colorInput.attributes('disabled')).toBeDefined();
    });
});
