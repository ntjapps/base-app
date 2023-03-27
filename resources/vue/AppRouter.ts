const landingPage = "/";
const dashboard = "/dashboard";
const profile = "/profile";
const serverHorizon = "/horizon";
const serverLogs = "/server-logs";
const userMan = "/user-man";

import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";
import { defineStore } from "pinia";

const routes: Array<RouteRecordRaw> = [
    {
        path: landingPage,
        name: "landingPage",
        component: () => import("./AuthPages/PgLogin.vue"),
    },
    {
        path: "/get-logout",
        name: "Logout",
        component: () => import("./AuthPages/PgLogout.vue"),
        meta: { requiresAuth: true },
    },
    {
        path: dashboard,
        name: "dashboard",
        component: () => import("./DashboardPages/PgDashboard.vue"),
    },
    {
        path: profile,
        name: "profile",
        component: () => import("./DashboardPages/PgProfile.vue"),
    },
    { path: serverHorizon, name: "serverHorizon", redirect: "/horizon" },
    {
        path: serverLogs,
        name: "serverLogs",
        component: () => import("./SuperPages/PgServerLog.vue"),
    },
    {
        path: userMan,
        name: "userMan",
        component: () => import("./SuperPages/PgUserMan.vue"),
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});

export const useWebStore = defineStore("web", {
    state: () => ({
        /** Define route here because if not defined and get from XHR it will be race condition */
        /** WEB requests */
        landingPage: landingPage,
        dashboard: dashboard,
        profile: profile,
        serverHorizon: serverHorizon,
        serverLogs: serverLogs,
        userMan: userMan,
    }),
});
