import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import PgPassport from './PgPassport.vue';

vi.mock('vue-clipboard3', () => ({
    default: () => ({ toClipboard: vi.fn() }),
}));

describe('PgPassport.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(PgPassport, {
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
                    DialogClientMan: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
