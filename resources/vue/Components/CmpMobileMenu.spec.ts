import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import CmpMobileMenu from './CmpMobileMenu.vue';

describe('CmpMobileMenu.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(CmpMobileMenu, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    MenuPanel: true,
                    Drawer: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
