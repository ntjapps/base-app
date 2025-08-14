import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useMainStore } from './AppState';
import { setActivePinia, createPinia } from 'pinia';
import { api } from './AppAxios';

describe('AppState (Pinia stores)', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('main store initializes and patches state from API', async () => {
        const store = useMainStore();
        vi.spyOn(api, 'postAppConst').mockResolvedValueOnce({
            status: 200,
            statusText: 'OK',
            headers: {},
            config: {},
            data: {
                title: 'Success',
                message: 'App constants loaded',
                data: {
                    appName: 'TestApp',
                    appVersion: '1.0.0',
                    userName: 'User',
                    userId: '1',
                    menuItems: { a: { label: 'A' }, b: { label: 'B' } },
                },
            },
        } as any);
        await store.init();
        expect(store.appName).toBe('TestApp');
        expect(store.appVersion).toBe('1.0.0');
        expect(store.userName).toBe('User');
        expect(store.userId).toBe('1');
        expect(store.menuItems.length).toBe(2);
    });

    it('browserSuppportCheck sets browserSuppport', () => {
        const store = useMainStore();
        // Simulate unsupported browser
        const origUA = navigator.userAgent;
        Object.defineProperty(navigator, 'userAgent', { value: 'OldBrowser', configurable: true });
        store.browserSuppportCheck();
        expect(store.browserSuppport).toBeTypeOf('boolean');
        Object.defineProperty(navigator, 'userAgent', { value: origUA });
    });

    it('updateExpandedKeysMenu updates expandedKeysMenu', () => {
        const store = useMainStore();
        store.updateExpandedKeysMenu('key1');
        expect(store.expandedKeysMenu).toHaveProperty('key1', true);
    });
});
