import axios from "axios";
import Echo from "laravel-echo";
import { defineStore } from "pinia";
import { supportedBrowsers } from "../ts/browser";
import { MenuItem } from "primevue/menuitem";

export const useWebApiStore = defineStore("webapi", {
    state: () => ({
        /** WEB for API requests */
        postLogin: "/post-login",
        postLogout: "/post-logout",
    }),
});

export const useApiStore = defineStore("api", {
    state: () => ({
        /** API request */
        postTokenLogout: "/api/post-token-revoke",
        postProfile: "/api/post-update-profile",
        appConst: "/api/post-app-const",
        getAllUserPermission: "/api/get-all-user-permission",
        logAgent: "/api/post-log-agent",
        getServerLogs: "/api/get-server-logs",
        getUserList: "/api/get-user-list",
        getUserRolePerm: "/api/get-user-role-perm",
        postUserManSubmit: "/api/post-user-man-submit",
        postDeleteUserManSubmit: "/api/post-delete-user-man-submit",
        postResetPasswordUserManSubmit:
            "/api/post-reset-password-user-man-submit",
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

export const useMainStore = defineStore("main", {
    state: () => ({
        /** Additional data */
        appName: import.meta.env.APP_NAME,
        userName: "",
        browserSuppport: true,
        menuItems: Array<MenuItemExtended>(),
        expandedKeysMenu: {},
        turnstileToken: "",
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
                        userName: response.data.userName,
                    });
                    this.$patch({
                        menuItems: JSON.parse(response.data.menuItems),
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
                axios.post(api.logAgent);
            } else {
                this.$patch({ browserSuppport: true });
            }
        },

        async spaCsrfToken() {
            /**
             * Get new CSRF Token set everytime app is created
             */
            axios.get("/sanctum/csrf-cookie").then(() => {
                console.log("csrf cookie init");
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

export const useEchoStore = defineStore("echo", {
    state: () => ({
        laravelEcho: new Echo({
            broadcaster: "pusher",
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? "mt1",
            wsHost: import.meta.env.VITE_PUSHER_HOST
                ? import.meta.env.VITE_PUSHER_HOST
                : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
            wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
            wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
            forceTLS:
                (import.meta.env.VITE_PUSHER_SCHEME ?? "https") === "https",
            enabledTransports: ["ws", "wss"],
        }),
    }),
});
