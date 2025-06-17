import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import PgLogin from './PgLogin.vue';

describe('PgLogin.vue', () => {
    it('mounts and renders login form', () => {
        const wrapper = mount(PgLogin, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpTurnstile: true,
                    CmpToast: true,
                    InputText: true,
                    Password: true,
                    LoginSpinner: true,
                    RouterLink: true,
                    RouterView: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
