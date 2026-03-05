import Echo from 'laravel-echo';
import { defineStore } from 'pinia';
import { supportedBrowsers } from '../ts/browser';
import { api } from './AppAxios';

interface Notification {
    id: string;
    type: string;
    notifiable_type: string;
    notifiable_id: number;
    data: Partial<Record<string, unknown>>;
    read_at: string | null;
    created_at: string;
    updated_at: string;
}

interface AppConstResponse {
    appName: string;
    appVersion: string;
    userName: string;
    userId: string;
    menuItems: Record<string, MenuItemExtended>;
    permissions?: string[];
}

interface MenuItemExtended {
    /** Unique id for Nuxt UI menu items (preferred) */
    id?: string;
    /** Legacy key that some APIs may return */
    key?: string;
    /** The `value` prop used by Nuxt UI's NavigationMenu for v-model/default-value */
    value?: string;
    /** Display label (required for rendering) */
    label: string;
    icon?: string;
    /** Nuxt UI uses `href` rather than `url` */
    href?: string;
    /** When present, auto-open this item by default on vertical menus */
    defaultOpen?: boolean;
    /** Children using Nuxt UI convention */
    children?: MenuItemExtended[];
    /** Keep items for backward compatibility with older payloads */
    items?: MenuItemExtended[];
}

// removed unused DeepPartial type

export const useMainStore = defineStore('main', {
    state: () => ({
        /** Additional data */
        notificationList: [] as Notification[],
        appName: '',
        appVersion: '',
        userName: '',
        userId: '',
        browserSuppport: true as boolean,
        menuItems: [] as MenuItemExtended[],
        permissions: [] as string[],
        expandedKeysMenu: {} as Record<string, boolean>,
        turnstileToken: '',
        menuVisible: false,
    }),

    actions: {
        async init() {
            /** Get Constant */
            try {
                const response = await api.postAppConst();
                // Some endpoints return { title, message, data: {...} }
                type AppConstOuter = AppConstResponse | { data?: Partial<AppConstResponse> };
                const outer = response.data as unknown as AppConstOuter;
                const hasDataKey = (o: AppConstOuter): o is { data: Partial<AppConstResponse> } =>
                    typeof o === 'object' && o !== null && 'data' in o && !!o.data;
                const payload: Partial<AppConstResponse> = hasDataKey(outer)
                    ? outer.data
                    : (outer as Partial<AppConstResponse>);
                // Keep RawMenuItem available for fallback normalizer
                type RawMenuItem = Record<string, unknown>;

                // Local fallback normalizer (used only if shared util import fails)
                const normalizeItem = (
                    raw: RawMenuItem | undefined,
                    key?: string,
                ): MenuItemExtended => {
                    const r = raw ?? {};
                    const id =
                        r['id'] ?? r['key'] ?? key ?? (r['label'] ? String(r['label']) : undefined);
                    const childrenRaw = (r['children'] ?? r['items'] ?? []) as unknown;
                    const children = Array.isArray(childrenRaw)
                        ? (childrenRaw as RawMenuItem[]).map((c) => normalizeItem(c, undefined))
                        : [];

                    return {
                        id: id ? String(id) : undefined,
                        key: r['key'] ? String(r['key']) : undefined,
                        label: (r['label'] ?? r['title'] ?? r['name'] ?? '') as string,
                        icon: r['icon'] ? String(r['icon']) : undefined,
                        href: r['href']
                            ? String(r['href'])
                            : r['url']
                              ? String(r['url'])
                              : undefined,
                        children: children.length ? children : undefined,
                    };
                };

                const menuRaw = payload.menuItems ?? [];
                // Use shared normalizer
                try {
                    // import synchronously since it's local and small
                    const { normalizeMenu } = await import('./utils/menu');
                    const menu = normalizeMenu(menuRaw);

                    // Update each field individually to avoid typing issues
                    this.$patch({
                        appName: payload.appName ?? '',
                        appVersion: payload.appVersion ?? '',
                        userName: payload.userName ?? '',
                        userId: payload.userId ?? '',
                        menuItems: menu as unknown as MenuItemExtended[],
                        permissions: Array.isArray(payload.permissions)
                            ? (payload.permissions as string[])
                            : [],
                    });
                } catch (error) {
                    // Log and fallback: keep original behavior on error
                    console.error(error);
                    const menu: MenuItemExtended[] = Array.isArray(menuRaw)
                        ? (menuRaw as RawMenuItem[]).map((m) => normalizeItem(m, undefined))
                        : Object.entries(menuRaw as unknown as Record<string, RawMenuItem>).map(
                              ([k, v]) => normalizeItem(v, k),
                          );

                    this.$patch({
                        appName: payload.appName ?? '',
                        appVersion: payload.appVersion ?? '',
                        userName: payload.userName ?? '',
                        userId: payload.userId ?? '',
                        menuItems: menu,
                        permissions: Array.isArray(payload.permissions)
                            ? (payload.permissions as string[])
                            : [],
                    });
                }

                // Fetch notifications only when userId is available
                await this.getNotificationList();
            } catch (error) {
                console.error(error);
            }
        },

        async browserSuppportCheck() {
            /**
             * Test if browser is compatible
             */
            if (!supportedBrowsers.test(navigator.userAgent)) {
                this.$patch({ browserSuppport: false });
                try {
                    await api.postLogAgent();
                } catch (error) {
                    console.error(error);
                }
            } else {
                this.$patch({ browserSuppport: true });
            }
        },

        async spaCsrfToken() {
            /**
             * Get new CSRF Token set everytime app is created
             */
            try {
                await fetch('/sanctum/csrf-cookie', {
                    method: 'GET',
                    credentials: 'include',
                });
                console.log('csrf cookie init');
            } catch (error) {
                console.error('Error setting CSRF cookie:', (error as Error).message);
            }
        },

        async getNotificationList() {
            /**
             * Get notification list
             */
            try {
                if (!this.userId) return;
                const response = await api.getNotificationList();
                this.$patch((state) => {
                    state.notificationList = (response.data.data as Notification[]) || [];
                });
            } catch (error) {
                console.error(error);
            }
        },

        updateExpandedKeysMenu(expandedKeys: string | string[] | Record<string, boolean>) {
            // Accept multiple input styles: single key, comma-separated string, array, or object
            let keysObj: Record<string, boolean> = {};
            if (!expandedKeys) {
                this.$patch((state) => {
                    state.expandedKeysMenu = {};
                });
                return;
            }

            if (typeof expandedKeys === 'string') {
                const parts = expandedKeys
                    .split(',')
                    .map((s) => s.trim())
                    .filter(Boolean);
                parts.forEach((k) => (keysObj[String(k)] = true));
            } else if (Array.isArray(expandedKeys)) {
                expandedKeys.forEach((k) => (keysObj[String(k)] = true));
            } else {
                keysObj = { ...expandedKeys };
            }

            this.$patch({
                expandedKeysMenu: {
                    ...(this.expandedKeysMenu ?? {}),
                    ...keysObj,
                },
            });
        },
    },
});

import type { EchoWithMethods } from './types/echo';

export const useEchoStore = defineStore('echo', {
    state: () => ({
        laravelEcho: undefined as unknown as EchoWithMethods | undefined,
    }),
    actions: {
        initEcho() {
            // Only initialize on the client
            if (typeof window === 'undefined') return;
            if (this.laravelEcho) return;

            this.laravelEcho = new Echo({
                broadcaster: 'pusher',
                key: import.meta.env.VITE_PUSHER_APP_KEY,
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
                wsHost: import.meta.env.VITE_PUSHER_HOST
                    ? import.meta.env.VITE_PUSHER_HOST
                    : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
                wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
                wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
                forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
            }) as unknown as EchoWithMethods;
        },
    },
});
