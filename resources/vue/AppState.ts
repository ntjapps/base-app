import Echo from 'laravel-echo';
import { defineStore } from 'pinia';
import { supportedBrowsers } from '../ts/browser';
import { MenuItem } from 'primevue/menuitem';
import { api } from './AppAxios';
import type { AxiosError } from 'axios';

interface Notification {
    id: string;
    type: string;
    notifiable_type: string;
    notifiable_id: number;
    data: Record<string, unknown>;
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
}

interface MenuItemExtended extends MenuItem {
    key: string;
    label: string;
    icon?: string;
    url?: string;
    command?: () => void;
    items?: Array<MenuItemExtended>;
}

export const useMainStore = defineStore('main', {
    state: () => ({
        /** Additional data */
        appName: import.meta.env.APP_NAME,
        appVersion: '',
        userName: '',
        userId: '',
        notificationList: [] as Notification[],
        browserSuppport: true,
        menuItems: Array<MenuItemExtended>(),
        expandedKeysMenu: {},
        turnstileToken: '',
        menuVisible: false,
    }),

    actions: {
        async init() {
            /** Get Constant */
            try {
                const response = await api.postAppConst();
                const data = response.data.data as AppConstResponse;
                // Update each field individually to avoid typing issues
                this.$patch((state) => {
                    state.appName = data.appName;
                    state.appVersion = data.appVersion;
                    state.userName = data.userName;
                    state.userId = data.userId;
                    state.menuItems = Object.values(data.menuItems);
                });
            } catch (error) {
                console.error((error as AxiosError).response?.data);
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
                    console.error((error as AxiosError).response?.data);
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
                    credentials: 'include'
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
                const response = await api.getNotificationList();
                this.$patch((state) => {
                    state.notificationList = (response.data.data as Notification[]) || [];
                });
            } catch (error) {
                console.error((error as AxiosError).response?.data);
            }
        },

        updateExpandedKeysMenu(expandedKeys: string) {
            this.$patch({
                expandedKeysMenu: {
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
