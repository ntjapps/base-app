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
});
