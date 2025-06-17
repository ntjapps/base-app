import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import CmpLayout from './CmpLayout.vue';

describe('CmpLayout.vue', () => {
    it('mounts and renders slot content', () => {
        const wrapper = mount(CmpLayout, {
            slots: {
                default: '<div>Test Content</div>',
            },
            global: {
                stubs: {
                    CmpHeader: true,
                    CmpFooter: true,
                    CmpMenu: true,
                    CmpMobileMenu: true,
                },
            },
        });
        expect(wrapper.text()).toContain('Test Content');
    });
});
