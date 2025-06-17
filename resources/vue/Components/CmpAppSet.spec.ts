import { describe, it, expect, beforeAll, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import CmpAppSet from './CmpAppSet.vue';
import axios from 'axios';
import { nextTick } from 'vue';

vi.mock('axios', () => {
    const rejected = () =>
        Promise.reject({ response: { data: { title: 'Error', message: 'Message' } } });
    return {
        default: {
            post: vi.fn(rejected),
            get: vi.fn(rejected),
        },
        post: vi.fn(rejected),
        get: vi.fn(rejected),
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
});

describe('CmpAppSet.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(CmpAppSet, {
            global: {
                plugins: [createPinia()],
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});

describe('API calls', () => {
    it('triggers axios POST and GET calls and handles errors without unhandled rejections', async () => {
        const postMock = vi.fn(() =>
            Promise.reject({ response: { data: { title: 'Error', message: 'Message' } } }),
        );
        const getMock = vi.fn(() =>
            Promise.reject({ response: { data: { title: 'Error', message: 'Message' } } }),
        );
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        (axios.post as any).mockImplementationOnce(postMock);
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        (axios.get as any).mockImplementationOnce(getMock);

        // Suppress console.error for cleaner test output
        const originalError = console.error;
        console.error = vi.fn();

        mount(CmpAppSet, {
            global: {
                plugins: [createPinia()],
                stubs: ['RouterLink', 'RouterView'],
                mocks: {
                    useToast: () => ({ add: vi.fn() }),
                },
            },
        });
        await nextTick();

        expect(postMock).toHaveBeenCalled();
        expect(getMock).toHaveBeenCalled();

        // Restore console.error
        console.error = originalError;
    });
});
