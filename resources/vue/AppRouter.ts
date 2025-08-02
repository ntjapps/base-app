const loginPage = '/login';
const dashboard = '/dashboard';
const profile = '/profile';
const serverHorizon = '/horizon';
const serverPulse = '/pulse';
const serverLogs = '/server-logs';
const userMan = '/user-man';
const roleMan = '/role-man';
const passportMan = '/passport-man';

import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import { defineStore } from 'pinia';

const routes: Array<RouteRecordRaw> = [
    {
        path: loginPage,
        name: 'loginPage',
        component: () => import('./AuthPages/PgLogin.vue'),
    },
    {
        path: '/get-logout',
        name: 'Logout',
        component: () => import('./AuthPages/PgLogout.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: dashboard,
        name: 'dashboard',
        component: () => import('./DashboardPages/PgDashboard.vue'),
    },
    {
        path: profile,
        name: 'profile',
        component: () => import('./DashboardPages/PgProfile.vue'),
    },
    { path: serverHorizon, name: 'serverHorizon', redirect: '/horizon' },
    {
        path: serverLogs,
        name: 'serverLogs',
        component: () => import('./SuperPages/PgServerLog.vue'),
    },
    {
        path: userMan,
        name: 'userMan',
        component: () => import('./SuperPages/PgUserMan.vue'),
    },
    {
        path: roleMan,
        name: 'roleMan',
        component: () => import('./SuperPages/PgRoleMan.vue'),
    },
    {
        path: passportMan,
        name: 'passportMan',
        component: () => import('./SuperPages/PgPassport.vue'),
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});

export const useWebStore = defineStore('web', {
    state: () => ({
        /** Define route here because if not defined and get from XHR it will be race condition */
        /** WEB requests */
        loginPage: loginPage,
        dashboard: dashboard,
        profile: profile,
        serverHorizon: serverHorizon,
        serverPulse: serverPulse,
        serverLogs: serverLogs,
        userMan: userMan,
        roleMan: roleMan,
        passportMan: passportMan,
    }),
});
