import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import DialogUserMan from './DialogUserMan.vue';
import { api } from '../AppAxios';

vi.mock('../AppAxios', () => ({
    api: {
        postUserManSubmit: vi.fn(() =>
            Promise.reject({ response: { data: { title: 'Error', message: 'Message' } } }),
        ),
        getUserList: vi.fn(() =>
            Promise.reject({ response: { data: { title: 'Error', message: 'Message' } } }),
        ),
    },
}));

describe('DialogUserMan.vue', () => {
    it('mounts and renders with required props', () => {
        const wrapper = mount(DialogUserMan, {
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
