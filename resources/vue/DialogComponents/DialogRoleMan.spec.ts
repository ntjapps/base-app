import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import DialogRoleMan from './DialogRoleMan.vue';
import { api } from '../AppAxios';

vi.mock('../AppAxios', () => ({
    api: {
        postRoleSubmit: vi.fn(() =>
            Promise.reject({ response: { data: { title: 'Error', message: 'Message' } } }),
        ),
    },
}));

describe('DialogRoleMan.vue', () => {
    it('mounts and renders with required props', () => {
        const wrapper = mount(DialogRoleMan, {
            props: {
                dialogOpen: true,
                dialogData: null,
                dialogTypeCreate: true,
            },
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpToast: { render: () => null, methods: { toastDisplay: () => {} } },
                    DataTable: true,
                    Column: true,
                    InputText: true,
                    RouterLink: true,
                    RouterView: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
