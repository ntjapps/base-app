import Echo from 'laravel-echo';
import { defineStore } from 'pinia';
import { supportedBrowsers } from '../ts/browser';
import { MenuItem } from 'primevue/menuitem';
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
    workerBackend?: {
        enabled: boolean;
        type: string;
    };
}

interface MenuItemExtended extends MenuItem {
    key: string;
    label: string;
    icon?: string;
    url?: string;
    command?: () => void;
    items?: Array<MenuItemExtended>;
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
        expandedKeysMenu: {} as Record<string, boolean>,
        turnstileToken: '',
        menuVisible: false,
        workerBackend: {
            enabled: false,
            type: 'celery',
        },
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
                const menu = payload.menuItems
                    ? Array.isArray(payload.menuItems)
                        ? (payload.menuItems as unknown as MenuItemExtended[])
                        : Object.values(payload.menuItems as Record<string, MenuItemExtended>)
                    : [];
                // Update each field individually to avoid typing issues
                this.$patch({
                    appName: payload.appName ?? '',
                    appVersion: payload.appVersion ?? '',
                    userName: payload.userName ?? '',
                    userId: payload.userId ?? '',
                    menuItems: menu,
                    workerBackend: payload.workerBackend ?? { enabled: false, type: 'celery' },
                });

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

        updateExpandedKeysMenu(expandedKeys: string) {
            this.$patch({
                expandedKeysMenu: {
                    ...(this.expandedKeysMenu ?? {}),
                    [expandedKeys]: true,
                },
            });
        },
    },
});

export const useEchoStore = defineStore('echo', {
    state: () => ({
        laravelEcho: new Echo({
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
        }),
    }),
});
