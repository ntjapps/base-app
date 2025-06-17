import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import CmpMenu from './CmpMenu.vue';

describe('CmpMenu.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(CmpMenu, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    MenuPanel: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
