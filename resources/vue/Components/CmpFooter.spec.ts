import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import CmpFooter from './CmpFooter.vue';

describe('CmpFooter.vue', () => {
    it('mounts and renders copyright', () => {
        const wrapper = mount(CmpFooter);
        expect(wrapper.text()).toContain('NTJ Application Studio');
    });
});
