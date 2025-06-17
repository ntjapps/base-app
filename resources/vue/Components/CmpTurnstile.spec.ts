import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import CmpTurnstile from './CmpTurnstile.vue';

describe('CmpTurnstile.vue', () => {
    it('mounts and renders container', () => {
        const wrapper = mount(CmpTurnstile, {
            global: {
                plugins: [createPinia()],
            },
        });
        expect(wrapper.find('#cf-container').exists()).toBe(true);
    });
});
