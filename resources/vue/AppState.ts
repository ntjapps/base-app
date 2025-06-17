import axios from 'axios';
import Echo from 'laravel-echo';
import { defineStore } from 'pinia';
import { supportedBrowsers } from '../ts/browser';
import { MenuItem } from 'primevue/menuitem';

export const useWebApiStore = defineStore('webapi', {
    state: () => ({
        /** WEB for API requests */
        postLogin: '/post-login',
        postLogout: '/post-logout',
    }),
});

export const useApiStore = defineStore('api', {
    state: () => ({
        /** API request */
        postTokenLogout: '/api/v1/post-token-revoke',
        postProfile: '/api/v1/post-update-profile',
        appConst: '/api/v1/post-app-const',
        getAllUserPermission: '/api/v1/get-all-user-permission',
        logAgent: '/api/v1/post-log-agent',
        getNotificationList: '/api/v1/get-notification-list',
        postNotificationAsRead: '/api/v1/post-notification-as-read',
        postNotificationClearAll: '/api/v1/post-notification-clear-all',
        getServerLogs: '/api/v1/get-server-logs',
        postClearAppCache: '/api/v1/post-clear-app-cache',
        getUserList: '/api/v1/get-user-list',
        getUserRolePerm: '/api/v1/get-user-role-perm',
        postUserManSubmit: '/api/v1/post-user-man-submit',
        postDeleteUserManSubmit: '/api/v1/post-delete-user-man-submit',
        postResetPasswordUserManSubmit: '/api/v1/post-reset-password-user-man-submit',
        getRoleList: '/api/v1/get-role-list',
        postRoleSubmit: '/api/v1/post-role-submit',
        postDeleteRoleSubmit: '/api/v1/post-delete-role-submit',
        postGetOauthClient: '/api/v1/oauth/post-get-oauth-client',
        postSubmitOauthClient: '/api/v1/oauth/post-submit-oauth-client',
        postUpdateOauthClient: '/api/v1/oauth/post-update-oauth-client',
        postDeleteOauthClient: '/api/v1/oauth/post-delete-oauth-client',
        postResetOauthSecret: '/api/v1/oauth/post-reset-oauth-secret',
        postCreateOauthClient: '/api/v1/oauth/post-create-oauth-client',
    }),
});

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
        notificationList: [],
        browserSuppport: true,
        menuItems: Array<MenuItemExtended>(),
        expandedKeysMenu: {},
        turnstileToken: '',
        menuVisible: false,
    }),

    actions: {
        init() {
            const api = useApiStore();

            /** Get Constant */
            axios
                .post(api.appConst)
                .then((response) => {
                    this.$patch({
                        appName: response.data.appName,
                    });
                    this.$patch({
                        appVersion: response.data.appVersion,
                    });
                    this.$patch({
                        userName: response.data.userName,
                    });
                    this.$patch({
                        userId: response.data.userId,
                    });
                    this.$patch({
                        menuItems: Object.values(response.data.menuItems),
                    });
                })
                .catch((error) => {
                    console.error(error.response.data);
                });
        },

        browserSuppportCheck() {
            const api = useApiStore();
            /**
             * Test if browser is compatible
             */
            if (!supportedBrowsers.test(navigator.userAgent)) {
                this.$patch({ browserSuppport: false });
                axios.post(api.logAgent).catch((error) => {
                    console.error(error.response);
                });
            } else {
                this.$patch({ browserSuppport: true });
            }
        },

        async spaCsrfToken() {
            /**
             * Get new CSRF Token set everytime app is created
             */
            axios
                .get('/sanctum/csrf-cookie')
                .then(() => {
                    console.log('csrf cookie init');
                })
                .catch((error) => {
                    console.error('Error setting CSRF cookie:', error.response);
                });
        },

        async getNotificationList() {
            /**
             * Get notification list
             */
            const api = useApiStore();
            axios
                .post(api.getNotificationList)
                .then((response) => {
                    this.$patch({ notificationList: response.data });
                })
                .catch((error) => {
                    console.error(error.response.data);
                });
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
