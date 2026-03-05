import { describe, it, expect, vi } from 'vitest';
import { router } from './AppRouter';

// Mock createWebHistory to avoid window error
vi.mock('vue-router', async (importOriginal) => {
    const actual = await importOriginal();
    return Object.assign({}, actual, {
        createWebHistory: () => ({}),
    });
});

describe('AppRouter', () => {
    it('should have expected named routes', () => {
        const names = router.getRoutes().map((r) => r.name);
        expect(names).toContain('loginPage');
        expect(names).toContain('dashboard');
        expect(names).toContain('profile');
        expect(names).toContain('serverLogs');
        expect(names).toContain('userMan');
        expect(names).toContain('roleMan');
        expect(names).toContain('passportMan');
    });

    it('should redirect /serverHorizon to /horizon', () => {
        const route = router.getRoutes().find((r) => r.name === 'serverHorizon');
        expect(route?.redirect).toBe('/horizon');
    });

    it('resolves lazy route components', async () => {
        const routes = router.getRoutes();
        const promises: Promise<unknown>[] = [];
        for (const r of routes) {
            const comp = (r.components as any)?.default;
            if (typeof comp === 'function') {
                promises.push(comp());
            }
        }
        const settled = await Promise.allSettled(promises);
        settled.forEach((r) => expect(r.status).toBe('fulfilled'));
    }, 20000);
});

import { setActivePinia, createPinia } from 'pinia';
import { useMainStore } from './AppState';
import { setDocumentTitle, useWebStore } from './AppRouter';

describe('document title behavior', () => {
    it('sets document title using meta title and appName', () => {
        const pinia = createPinia();
        setActivePinia(pinia);
        const main = useMainStore();
        main.$patch({ appName: 'TestApp' });

        // meta title present
        setDocumentTitle({
            meta: { title: 'WhatsApp Templates' },
            name: 'whatsappTemplateMan',
        } as any);
        expect(document.title).toBe('WhatsApp Templates - TestApp');

        // no meta title, fallback to route name
        setDocumentTitle({ meta: {}, name: 'dashboard' } as any);
        expect(document.title).toBe('dashboard - TestApp');
    });

    it('falls back to existing document title suffix when appName missing', () => {
        const pinia = createPinia();
        setActivePinia(pinia);
        const main = useMainStore();
        main.$patch({ appName: '' });

        document.title = 'Old Title - OldApp';
        setDocumentTitle({ meta: { title: 'New' }, name: 'x' } as any);
        expect(document.title).toBe('New - OldApp');

        setDocumentTitle({ meta: {}, name: undefined } as any);
        expect(document.title).toBe('OldApp');
    });

    it('does not change title if store access throws', async () => {
        vi.resetModules();
        vi.doMock('vue-router', async (importOriginal) => {
            const actual = await importOriginal();
            return Object.assign({}, actual, {
                createWebHistory: () => ({}),
            });
        });
        vi.doMock('./AppState', () => ({
            useMainStore: () => {
                throw new Error('boom');
            },
        }));
        const { setDocumentTitle: setDoc } = await import('./AppRouter');
        document.title = 'Keep Me';
        setDoc({ meta: { title: 'X' }, name: 'n' } as any);
        expect(document.title).toBe('Keep Me');
    });
});

describe('useWebStore', () => {
    it('exposes known route paths', () => {
        const pinia = createPinia();
        setActivePinia(pinia);
        const web = useWebStore();
        expect(web.loginPage).toBe('/login');
        expect(web.dashboard).toBe('/dashboard');
        expect(web.whatsappTemplateMan).toBe('/whatsapp-templates-man');
    });
});
