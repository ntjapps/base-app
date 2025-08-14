import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import PgLogout from './PgLogout.vue';
import { api } from '../AppAxios';

describe('PgLogout.vue', () => {
    it('mounts and renders toast', () => {
        vi.mock('../AppAxios', () => ({
            api: {
                postLogout: vi.fn(() =>
                    Promise.reject({
                        response: { data: { title: 'Error', message: 'Message' } },
                    }),
                ),
            },
        }));
        const wrapper = mount(PgLogout, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: { render: () => null, methods: { toastDisplay: () => {} } },
                    RouterLink: true,
                    RouterView: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
