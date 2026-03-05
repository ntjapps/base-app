import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useMainStore, useEchoStore } from './AppState';
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
        // Normalized items should have ids derived from the object keys
        expect(store.menuItems[0].id).toBe('a');
        expect(store.menuItems[0].label).toBe('A');

        // Echo should be initialized on client by init call
        const echoStore = useEchoStore();
        expect(echoStore.laravelEcho).toBeUndefined();
        echoStore.initEcho();
        expect(echoStore.laravelEcho).toBeDefined();
    });

    it('updateExpandedKeysMenu accepts comma string and array', () => {
        const store = useMainStore();
        // comma separated string
        store.updateExpandedKeysMenu('a,b');
        expect(store.expandedKeysMenu).toHaveProperty('a', true);
        expect(store.expandedKeysMenu).toHaveProperty('b', true);

        // array input
        store.updateExpandedKeysMenu(['c', 'd']);
        expect(store.expandedKeysMenu).toHaveProperty('c', true);
        expect(store.expandedKeysMenu).toHaveProperty('d', true);
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

    it('updateExpandedKeysMenu clears on falsy input and accepts object maps', () => {
        const store = useMainStore();
        store.updateExpandedKeysMenu('a');
        expect(store.expandedKeysMenu).toHaveProperty('a', true);

        store.updateExpandedKeysMenu('' as any);
        expect(Object.keys(store.expandedKeysMenu)).toHaveLength(0);

        store.updateExpandedKeysMenu({ k1: true, k2: true });
        expect(store.expandedKeysMenu).toHaveProperty('k1', true);
        expect(store.expandedKeysMenu).toHaveProperty('k2', true);
    });

    it('getNotificationList is a no-op when userId is empty', async () => {
        const store = useMainStore();
        store.$patch({ userId: '' });
        const spy = vi.spyOn(api, 'getNotificationList');
        await store.getNotificationList();
        expect(spy).not.toHaveBeenCalled();
    });

    it('getNotificationList patches notificationList from API', async () => {
        const store = useMainStore();
        store.$patch({ userId: '1' });

        vi.spyOn(api, 'getNotificationList').mockResolvedValueOnce({
            data: { data: [{ id: 'n1', type: 't', notifiable_type: 'u', notifiable_id: 1 }] },
        } as any);

        await store.getNotificationList();
        expect(store.notificationList.length).toBe(1);
    });

    it('spaCsrfToken uses fetch and handles failures', async () => {
        const store = useMainStore();

        const ok = vi.fn().mockResolvedValueOnce({});
        (globalThis as any).fetch = ok;
        await store.spaCsrfToken();
        expect(ok).toHaveBeenCalledWith('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'include',
        });

        const bad = vi.fn().mockRejectedValueOnce(new Error('x'));
        (globalThis as any).fetch = bad;
        await store.spaCsrfToken();
        expect(bad).toHaveBeenCalled();
    });

    it('browserSuppportCheck sets browserSuppport true on supported user agent', async () => {
        const store = useMainStore();
        const orig = navigator.userAgent;
        Object.defineProperty(navigator, 'userAgent', {
            value: 'Chrome/120.0.0.0',
            configurable: true,
        });
        await store.browserSuppportCheck();
        expect(store.browserSuppport).toBe(true);
        Object.defineProperty(navigator, 'userAgent', { value: orig });
    });

    it('getNotificationList logs on error', async () => {
        const store = useMainStore();
        store.$patch({ userId: '1' });
        vi.spyOn(api, 'getNotificationList').mockRejectedValueOnce(new Error('x'));
        const err = vi.spyOn(console, 'error').mockImplementation(() => undefined);
        await store.getNotificationList();
        expect(err).toHaveBeenCalled();
        err.mockRestore();
    });
});
