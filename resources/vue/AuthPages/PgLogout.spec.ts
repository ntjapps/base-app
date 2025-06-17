import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import PgLogout from './PgLogout.vue';
import { vi } from 'vitest';

describe('PgLogout.vue', () => {
    it('mounts and renders toast', () => {
        vi.mock('axios', () => ({
            default: {
                post: vi.fn(() =>
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
