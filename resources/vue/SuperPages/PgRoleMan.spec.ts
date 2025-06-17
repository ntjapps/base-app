import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import PgRoleMan from './PgRoleMan.vue';

describe('PgRoleMan.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(PgRoleMan, {
            props: {
                appName: 'TestApp',
                greetings: 'Hello',
                expandedKeysProps: 'key1',
            },
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: true,
                    CmpLayout: true,
                    Dialog: true,
                    DataTable: true,
                    Column: true,
                    InputText: true,
                    DialogRoleMan: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
