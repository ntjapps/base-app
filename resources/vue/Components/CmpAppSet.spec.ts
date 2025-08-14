import { describe, it, expect, beforeAll, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import CmpAppSet from './CmpAppSet.vue';
import { api } from '../AppAxios';
import { nextTick } from 'vue';
import PrimeVue from 'primevue/config';
import Echo from 'laravel-echo';

vi.mock('../AppAxios', () => {
    const rejected = () =>
        Promise.reject({ response: { data: { title: 'Error', message: 'Message' } } });
    return {
        api: {
            postGetCurrentAppVersion: vi.fn(rejected),
            getLogout: vi.fn(rejected)
        }
    };
});

declare global {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    var __originalConsoleError: ((msg?: any, ...args: any[]) => void) | undefined;
}

beforeAll(() => {
    vi.spyOn(console, 'error').mockImplementation((msg: unknown) => {
        if (typeof msg === 'string' && msg.includes('AggregateError')) return;
        return undefined;
    });
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (globalThis as any).Pusher = vi.fn();
    vi.spyOn(Echo.prototype as any, 'connect').mockImplementation(() => undefined);
});

describe('CmpAppSet.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(CmpAppSet, {
            global: {
                plugins: [createPinia(), PrimeVue],
                stubs: ['RouterLink', 'RouterView'],
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});

describe('API calls', () => {
    it('mounts without unhandled rejections when API calls fail', async () => {
        const mockError = { response: { data: { title: 'Error', message: 'Message' } } };
        (api.postGetCurrentAppVersion as any).mockRejectedValue(mockError);
        (api.getLogout as any).mockRejectedValue(mockError);

        // Suppress console.error for cleaner test output
        const originalError = console.error;
        console.error = vi.fn();

        const wrapper = mount(CmpAppSet, {
            global: {
                plugins: [createPinia(), PrimeVue],
                stubs: ['RouterLink', 'RouterView'],
                mocks: {
                    useToast: () => ({ add: vi.fn() }),
                },
            },
        });
        await nextTick();

        expect(wrapper.exists()).toBe(true);

        // Restore console.error
        console.error = originalError;
    });
});
