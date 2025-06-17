import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import PgUserMan from './PgUserMan.vue';

describe('PgUserMan.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(PgUserMan, {
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
                    DialogUserMan: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
