import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useMainStore } from './AppState';
import { useWebStore } from './AppRouter';
import { api } from './AppAxios';
import { setActivePinia, createPinia } from 'pinia';

describe('Logout Process', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        // Reset window.location mock if needed
        vi.stubGlobal('location', {
            ...window.location,
            href: 'http://localhost/',
            replace: vi.fn(),
        });
    });

    it('api.getLogout hits the correct server endpoint', async () => {
        const spy = vi.spyOn(api as any, 'get').mockResolvedValue({ data: {} });
        await api.getLogout();
        expect(spy).toHaveBeenCalledWith('/auth/get-logout', {});
    });

    it('api.postLogout hits the correct server endpoint', async () => {
        const spy = vi.spyOn(api as any, 'post').mockResolvedValue({ data: {} });
        await api.postLogout();
        expect(spy).toHaveBeenCalledWith('/auth/post-logout', {}, {});
    });

    it('logout route is registered in Vue Router', async () => {
        const { router } = await import('./AppRouter');
        const logoutRoute = router.getRoutes().find((r) => r.path === '/auth/get-logout');
        expect(logoutRoute).toBeDefined();
        expect(logoutRoute?.name).toBe('authGetLogout');
    });
});
