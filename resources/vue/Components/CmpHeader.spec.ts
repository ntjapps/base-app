import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import CmpHeader from './CmpHeader.vue';

describe('CmpHeader.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(CmpHeader, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpPusherState: true,
                    CmpClearCacheButton: true,
                    UButton: true,
                    RouterLink: true,
                    RouterView: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
