import { describe, it, expect, beforeAll, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import CmpPusherState from './CmpPusherState.vue';

beforeAll(() => {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (globalThis as any).Pusher = vi.fn();
});

describe('CmpPusherState.vue', () => {
    it('mounts and renders without errors', () => {
        const wrapper = mount(CmpPusherState, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    IconChartBar: true,
                    TooltipRoot: true,
                    TooltipProvider: true,
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
