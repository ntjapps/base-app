import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import PgServerLog from './PgServerLog.vue';
import { vi } from 'vitest';

vi.mock('axios', () => ({
    default: {
        post: vi.fn(() =>
            Promise.reject({ response: { data: { title: 'Error', message: 'Message' } } }),
        ),
    },
}));

describe('PgServerLog.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(PgServerLog, {
            props: {
                appName: 'TestApp',
                greetings: 'Hello',
                expandedKeysProps: 'key1',
            },
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpLayout: true,
                    DataTable: true,
                    Column: true,
                    DatePicker: true,
                    Select: true,
                    InputText: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
