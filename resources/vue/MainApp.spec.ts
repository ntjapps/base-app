import { describe, it, expect, vi, beforeAll } from 'vitest';
import { mount } from '@vue/test-utils';
import MainApp from './MainApp.vue';

declare global {
    var __originalConsoleError: ((msg?: any, ...args: any[]) => void) | undefined;
}

beforeAll(() => {
    vi.spyOn(console, 'error').mockImplementation((msg: unknown) => {
        if (typeof msg === 'string' && msg.includes('AggregateError')) return;
        return undefined;
    });
});

describe('MainApp.vue', () => {
    it('renders with required props and child components', () => {
        const wrapper = mount(MainApp, {
            props: {
                appName: 'TestApp',
                greetings: 'Hello',
                expandedKeysProps: 'key1',
            },
            global: {
                stubs: {
                    CmpAppSet: { template: '<div>CmpAppSet</div>' },
                    RouterView: { template: '<div>RouterView</div>' },
                    UApp: { template: '<div><slot /></div>' },
                },
            },
        });
        expect(wrapper.text()).toContain('CmpAppSet');
        expect(wrapper.text()).toContain('RouterView');
    });
});
