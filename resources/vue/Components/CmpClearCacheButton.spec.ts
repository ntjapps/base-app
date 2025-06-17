import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import CmpClearCacheButton from './CmpClearCacheButton.vue';

describe('CmpClearCacheButton.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(CmpClearCacheButton, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: true,
                    UTooltip: true,
                    UButton: true,
                    TooltipRoot: true,
                    TooltipProvider: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
