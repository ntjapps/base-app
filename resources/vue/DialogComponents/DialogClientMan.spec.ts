import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import DialogClientMan from './DialogClientMan.vue';

vi.mock('vue-clipboard3', () => ({
    default: () => ({ toClipboard: vi.fn() }),
}));

describe('DialogClientMan.vue', () => {
    it('mounts and renders with required props', () => {
        const wrapper = mount(DialogClientMan, {
            props: {
                dialogOpen: true,
                dialogData: null,
                dialogTypeCreate: true,
            },
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: { render: () => null, methods: { toastDisplay: () => {} } },
                    InputText: true,
                    RouterLink: true,
                    RouterView: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
