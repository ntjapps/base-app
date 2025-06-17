import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import PgDashboard from './PgDashboard.vue';

describe('PgDashboard.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(PgDashboard, {
            props: {
                appName: 'TestApp',
                greetings: 'Hello',
                expandedKeysProps: 'key1',
            },
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpLayout: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
