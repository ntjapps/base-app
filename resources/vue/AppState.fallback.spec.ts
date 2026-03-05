import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';

describe('AppState init fallback', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('falls back to local menu normalizer when shared import fails', async () => {
        vi.resetModules();
        vi.doMock('./utils/menu', () => ({
            normalizeMenu: () => {
                throw new Error('fail');
            },
        }));

        const { api } = await import('./AppAxios');
        vi.spyOn(api, 'postAppConst').mockResolvedValueOnce({
            data: {
                data: {
                    appName: 'X',
                    appVersion: '1',
                    userName: 'U',
                    userId: '1',
                    menuItems: [{ label: 'A', items: [{ label: 'C' }] }, { label: 'B' }],
                },
            },
        } as any);

        const { useMainStore } = await import('./AppState');
        const store = useMainStore();
        await store.init();
        expect(store.menuItems.length).toBe(2);
        expect(store.menuItems[0].label).toBe('A');
        expect(store.menuItems[0].children?.[0].label).toBe('C');
    });
});
