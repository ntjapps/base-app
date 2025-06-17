import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import CmpTesting from './CmpTesting.vue';

describe('CmpTesting.vue', () => {
    it('mounts and renders static content', () => {
        const wrapper = mount(CmpTesting);
        expect(wrapper.text()).toContain('Testing Cypress');
    });
});
