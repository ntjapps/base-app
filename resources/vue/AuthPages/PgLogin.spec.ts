import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import PgLogin from './PgLogin.vue';
import { api } from '../AppAxios';
import { useMainStore } from '../AppState';
import CmpToastStub from '../../../tests/mocks/CmpToastStub';

// Mock the API
vi.mock('../AppAxios', () => ({
    api: {
        postLogin: vi.fn(),
        setTurnstileReset: vi.fn(),
    },
}));

describe('PgLogin.vue', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        // Mock window.location.href
        delete (window as any).location;
        (window as any).location = { href: '' };
    });

    it('mounts and renders login form', () => {
        const wrapper = mount(PgLogin, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    CmpTurnstile: true,
                    CmpToast: CmpToastStub,
                    UInput: true,
                    UButton: true,
                    UIcon: true,
                    StdButton: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
        expect(wrapper.text()).toContain('Login to your account');
    });

    it('simulates full user login flow with typing and clicking', async () => {
        const pinia = createPinia();
        setActivePinia(pinia);
        const mainStore = useMainStore();

        // Set turnstile token to simulate captcha completion
        mainStore.turnstileToken = 'mock-turnstile-token';

        (api.postLogin as any).mockResolvedValueOnce({ success: true });

        const wrapper = mount(PgLogin, {
            global: {
                plugins: [pinia],
                stubs: {
                    CmpTurnstile: true,
                    CmpToast: CmpToastStub,
                    UIcon: true,
                    UInput: {
                        template:
                            '<input v-model="modelValue" :type="type" :placeholder="placeholder" @input="$emit(\'update:modelValue\', $event.target.value)" @keypress="$emit(\'keypress\', $event)" @keyup="$emit(\'keyup\', $event)" />',
                        props: ['modelValue', 'type', 'placeholder'],
                    },
                    StdButton: {
                        template: '<button @click="$emit(\'click\', $event)"><slot /></button>',
                        props: ['variant', 'label', 'class'],
                        emits: ['click'],
                    },
                },
            },
        });

        await wrapper.vm.$nextTick();

        // 1. Simulate User Typing in Username Field
        const usernameInput = wrapper.find('#username');
        expect(usernameInput.exists()).toBe(true);
        await usernameInput.setValue('admin');
        await wrapper.vm.$nextTick();

        // Verify v-model updated
        expect((wrapper.vm as any).username).toBe('admin');

        // 2. Simulate User Typing in Password Field
        const passwordInput = wrapper.find('#password');
        expect(passwordInput.exists()).toBe(true);
        await passwordInput.setValue('password123');
        await wrapper.vm.$nextTick();

        // Verify v-model updated
        expect((wrapper.vm as any).password).toBe('password123');

        // 3. Simulate User Clicking Login Button
        const loginButton = wrapper.find('button');
        expect(loginButton.exists()).toBe(true);
        await loginButton.trigger('click');
        await wrapper.vm.$nextTick();

        // 4. Verify API was called with correct credentials
        expect(api.postLogin).toHaveBeenCalledTimes(1);
        expect(api.postLogin).toHaveBeenCalledWith({
            username: 'admin',
            password: 'password123',
            token: 'mock-turnstile-token',
        });
    });

    it('does not submit when fields are empty', async () => {
        const pinia = createPinia();
        setActivePinia(pinia);

        const wrapper = mount(PgLogin, {
            global: {
                plugins: [pinia],
                stubs: {
                    CmpTurnstile: true,
                    CmpToast: CmpToastStub,
                    UInput: true,
                    UButton: true,
                    UIcon: true,
                    StdButton: true,
                },
            },
        });

        await wrapper.vm.$nextTick();

        // Simulate clicking login without entering credentials
        const loginButton = wrapper.findComponent({ name: 'StdButton' });
        await loginButton.trigger('click');
        await wrapper.vm.$nextTick();

        // API should NOT be called
        expect(api.postLogin).not.toHaveBeenCalled();
    });

    it('does not submit when turnstile token is missing', async () => {
        const pinia = createPinia();
        setActivePinia(pinia);
        const mainStore = useMainStore();

        // Explicitly set turnstile token to empty
        mainStore.turnstileToken = '';

        const wrapper = mount(PgLogin, {
            global: {
                plugins: [pinia],
                stubs: {
                    CmpTurnstile: true,
                    CmpToast: CmpToastStub,
                    UInput: {
                        template:
                            '<input v-model="modelValue" :type="type" @input="$emit(\'update:modelValue\', $event.target.value)" />',
                        props: ['modelValue', 'type'],
                    },
                    UButton: true,
                    UIcon: true,
                    StdButton: true,
                },
            },
        });

        await wrapper.vm.$nextTick();

        // Fill in credentials
        const usernameInput = wrapper.find('#username');
        const passwordInput = wrapper.find('#password');
        await usernameInput.setValue('admin');
        await passwordInput.setValue('password123');

        // Try to submit
        const loginButton = wrapper.findComponent({ name: 'StdButton' });
        await loginButton.trigger('click');
        await wrapper.vm.$nextTick();

        // API should NOT be called without turnstile token
        expect(api.postLogin).not.toHaveBeenCalled();
    });

    it('supports Enter key to submit login', async () => {
        const pinia = createPinia();
        setActivePinia(pinia);
        const mainStore = useMainStore();
        mainStore.turnstileToken = 'mock-token';

        (api.postLogin as any).mockResolvedValueOnce({ success: true });

        const wrapper = mount(PgLogin, {
            global: {
                plugins: [pinia],
                stubs: {
                    CmpTurnstile: true,
                    CmpToast: CmpToastStub,
                    UIcon: true,
                    UInput: {
                        template:
                            '<input v-model="modelValue" :type="type" @input="$emit(\'update:modelValue\', $event.target.value)" @keypress="$emit(\'keypress\', $event)" @keyup="$emit(\'keyup\', $event)" />',
                        props: ['modelValue', 'type'],
                    },
                },
            },
        });

        await wrapper.vm.$nextTick();

        // Fill credentials
        const usernameInput = wrapper.find('#username');
        const passwordInput = wrapper.find('#password');
        await usernameInput.setValue('admin');
        await passwordInput.setValue('password123');

        // Press Enter on password field
        await passwordInput.trigger('keyup.enter');
        await wrapper.vm.$nextTick();

        expect(api.postLogin).toHaveBeenCalled();
        const [payload] = (api.postLogin as any).mock.calls[0];
        expect(payload).toEqual({
            username: 'admin',
            password: 'password123',
            token: 'mock-token',
        });
    });
});
