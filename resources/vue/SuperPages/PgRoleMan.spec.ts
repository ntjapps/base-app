import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import ui from '@nuxt/ui/vue-plugin';
import PgRoleMan from './PgRoleMan.vue';
import CmpToastStub from '../../../tests/mocks/CmpToastStub';

describe('PgRoleMan.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(PgRoleMan, {
            global: {
                plugins: [createPinia(), ui],
                stubs: {
                    CmpToast: CmpToastStub,
                    CmpLayout: true,
                    Dialog: true,
                    DialogRoleMan: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
