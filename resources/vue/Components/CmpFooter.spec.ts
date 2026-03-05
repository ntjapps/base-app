import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import CmpFooter from './CmpFooter.vue';

describe('CmpFooter.vue', () => {
    it('mounts and renders copyright', () => {
        const pinia = createPinia();
        setActivePinia(pinia);
        const wrapper = mount(CmpFooter, { global: { plugins: [pinia] } });
        expect(wrapper.text()).toContain('All rights reserved.');
    });
});
