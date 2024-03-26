import axios from "axios";
import Echo from "laravel-echo";
import { defineStore } from "pinia";
import { supportedBrowsers } from "../ts/browser";
import { MenuItem } from "primevue/menuitem";
import { useToast } from "primevue/usetoast";

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
        getNotificationList: "/api/get-notification-list",
        postNotificationAsRead: "/api/post-notification-as-read",
        postNotificationClearAll: "/api/post-notification-clear-all",
        getServerLogs: "/api/get-server-logs",
        postClearAppCache: "/api/post-clear-app-cache",
        getUserList: "/api/get-user-list",
        getUserRolePerm: "/api/get-user-role-perm",
        postUserManSubmit: "/api/post-user-man-submit",
        postDeleteUserManSubmit: "/api/post-delete-user-man-submit",
        postResetPasswordUserManSubmit:
            "/api/post-reset-password-user-man-submit",
        getRoleList: "/api/get-role-list",
        postRoleSubmit: "/api/post-role-submit",
        postDeleteRoleSubmit: "/api/post-delete-role-submit",
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
        appVersion: "",
        userName: "",
        userId: "",
        notificationList: [],
        browserSuppport: true,
        menuItems: Array<MenuItemExtended>(),
        expandedKeysMenu: {},
        turnstileToken: "",
    }),

    actions: {
        init() {
            const api = useApiStore();
            const echo = useEchoStore();
            const toast = useToast();

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

                    /** Register Notification Broadcast */
                    if (
                        response.data.userId !== "" &&
                        response.data.userId !== undefined &&
                        response.data.userId !== null
                    ) {
                        echo.laravelEcho.leave(
                            "App.Models.User." + response.data.userId,
                        );

                        echo.laravelEcho
                            ?.private("App.Models.User." + response.data.userId)
                            .notification(
                                (notification: {
                                    severity:
                                        | "success"
                                        | "info"
                                        | "warn"
                                        | "error"
                                        | "secondary"
                                        | "contrast"
                                        | undefined;
                                    summary: string | undefined;
                                    message: string | undefined;
                                    life: number | undefined;
                                }) => {
                                    toast.add({
                                        severity: notification.severity,
                                        summary: notification.summary,
                                        detail: notification.message,
                                        life: notification.life,
                                    });
                                },
                            );
                    }
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
