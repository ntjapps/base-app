import axios from "axios";
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
        postProfile: "/api/post-update-profile",
        appConst: "/api/post-app-const",
        getAllUserPermission: "/api/get-all-user-permission",
        logAgent: "/api/post-log-agent",
        getServerLogs: "/api/get-server-logs",
        getUserList: "/api/get-user-list",
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
        browserSuppport: true,
        menuItems: Array<MenuItemExtended>(),

        appName: "Base App",
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

        spaCsrfToken() {
            /**
             * Get new CSRF Token set everytime app is created
             */
            axios.get("/sanctum/csrf-cookie").then(() => {
                console.log("csrf cookie init");
            });
        },
    },
});
