import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import PgProfile from './PgProfile.vue';

describe('PgProfile.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(PgProfile, {
            props: {
                appName: 'TestApp',
                greetings: 'Hello',
                expandedKeysProps: 'key1',
            },
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpLayout: true,
                    CmpToast: true,
                    InputText: true,
                    Password: true,
                    Message: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
