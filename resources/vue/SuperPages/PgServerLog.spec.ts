import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import ui from '@nuxt/ui/vue-plugin';
import PgServerLog from './PgServerLog.vue';
import { api } from '../AppAxios';

vi.mock('../AppAxios', () => ({
    api: {
        getServerLogs: vi.fn(() =>
            Promise.resolve({
                data: {
                    current_page: 1,
                    data: [],
                    first_page_url: '',
                    from: 0,
                    last_page: 1,
                    last_page_url: '',
                    links: [],
                    next_page_url: null,
                    path: '',
                    per_page: 20,
                    prev_page_url: null,
                    to: 0,
                    total: 0,
                },
            }),
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
                plugins: [createPinia(), ui],
                stubs: {
                    CmpLayout: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
