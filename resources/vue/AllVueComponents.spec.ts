import { describe, it, expect, beforeEach } from 'vitest';
import { shallowMount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createRouter, createMemoryHistory } from 'vue-router';

const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/', name: 'home', component: { template: '<div />' } }],
});

const defaultValueForProp = (def: any) => {
    const raw = def?.type;
    const types = Array.isArray(raw) ? raw : raw ? [raw] : [];
    if (types.includes(String)) return 'x';
    if (types.includes(Number)) return 1;
    if (types.includes(Boolean)) return false;
    if (types.includes(Array)) return [];
    if (types.includes(Object)) return {};
    if (types.includes(Function)) return () => undefined;
    return 'x';
};

const buildRequiredProps = (component: any): Record<string, unknown> => {
    const props = component?.props;
    if (!props || typeof props !== 'object') return {};
    const out: Record<string, unknown> = {};
    Object.entries(props).forEach(([key, def]) => {
        const d = def as any;
        if (d && typeof d === 'object' && d.required) {
            out[key] = defaultValueForProp(d);
        }
    });
    return out;
};

describe('All Vue components', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('mounts all SFCs without throwing', async () => {
        await router.push('/');
        await router.isReady();

        const modules = import.meta.glob('./**/*.vue', { eager: true }) as Record<string, any>;
        const entries = Object.entries(modules);

        for (const [, mod] of entries) {
            const component = mod?.default ?? mod;
            const props = buildRequiredProps(component);
            const wrapper = shallowMount(component, {
                props,
                global: {
                    plugins: [router],
                },
            });
            wrapper.unmount();
        }

        expect(entries.length).toBeGreaterThan(0);
    });
});
