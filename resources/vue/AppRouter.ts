const landingPage = '/';
const privacyPolicy = '/privacy-policy';
const privacyPolicyWaagent = '/privacy-policy-waagent';
const termsOfService = '/terms-of-service';
const termsOfServiceWaagent = '/terms-of-service-waagent';
const loginPage = '/login';
const authGetLogout = '/auth/get-logout';
const dashboard = '/dashboard';
const profile = '/profile';
const serverHorizon = '/horizon';
const routeAnalytics = '/route-analytics';
const serverLogs = '/server-logs';
const userMan = '/user-man';
const roleMan = '/role-man';
const passportMan = '/passport-man';
const divisionMan = '/division-man';
const tagMan = '/tag-man';
const aiModelInstructionMan = '/ai-model-instruction-man';
const whatsappMan = '/whatsapp-man';
const whatsappTemplateMan = '/whatsapp-templates-man';

import {
    createRouter,
    createWebHistory,
    RouteRecordRaw,
    type RouteLocationNormalized,
} from 'vue-router';
import { defineStore } from 'pinia';
import { useMainStore } from './AppState';

const routes: Array<RouteRecordRaw> = [
    {
        path: landingPage,
        name: 'landingPage',
        component: () => import('./LandingPages/PgLanding.vue'),
        meta: { title: 'Welcome' },
    },
    {
        path: privacyPolicy,
        name: 'privacyPolicy',
        component: () => import('./LandingPages/PgPrivacyPolicy.vue'),
        meta: { title: 'Privacy Policy' },
    },
    {
        path: privacyPolicyWaagent,
        name: 'privacyPolicyWaagent',
        component: () => import('./LandingPages/PgPrivacyPolicyWaAgentApp.vue'),
        meta: { title: 'Privacy Policy (WA Agent)' },
    },
    {
        path: termsOfService,
        name: 'termsOfService',
        component: () => import('./LandingPages/PgTermsOfService.vue'),
        meta: { title: 'Terms of Service' },
    },
    {
        path: termsOfServiceWaagent,
        name: 'termsOfServiceWaagent',
        component: () => import('./LandingPages/PgTermsOfServiceWaAgentApp.vue'),
        meta: { title: 'Terms of Service (WA Agent)' },
    },
    {
        path: loginPage,
        name: 'loginPage',
        component: () => import('./AuthPages/PgLogin.vue'),
        meta: { title: 'Login' },
    },
    {
        path: authGetLogout,
        name: 'authGetLogout',
        component: () => import('./AuthPages/PgLogout.vue'),
        meta: { title: 'Logging out...' },
    },
    {
        path: dashboard,
        name: 'dashboard',
        component: () => import('./DashboardPages/PgDashboard.vue'),
        meta: { title: 'Dashboard' },
    },
    {
        path: profile,
        name: 'profile',
        component: () => import('./DashboardPages/PgProfile.vue'),
        meta: { title: 'Profile' },
    },
    { path: serverHorizon, name: 'serverHorizon', redirect: '/horizon' },
    {
        path: routeAnalytics,
        name: 'routeAnalytics',
        component: () => import('./SuperPages/PgRouteAnalytics.vue'),
        meta: { title: 'Route Analytics' },
    },
    {
        path: serverLogs,
        name: 'serverLogs',
        component: () => import('./SuperPages/PgServerLog.vue'),
        meta: { title: 'Server Logs' },
    },
    {
        path: userMan,
        name: 'userMan',
        component: () => import('./SuperPages/PgUserMan.vue'),
        meta: { title: 'User Management' },
    },
    {
        path: roleMan,
        name: 'roleMan',
        component: () => import('./SuperPages/PgRoleMan.vue'),
        meta: { title: 'Role Management' },
    },
    {
        path: passportMan,
        name: 'passportMan',
        component: () => import('./SuperPages/PgPassport.vue'),
        meta: { title: 'Passport Management' },
    },
    {
        path: divisionMan,
        name: 'divisionMan',
        component: () => import('./SuperPages/PgDivisionMan.vue'),
        meta: { title: 'Division Management' },
    },
    {
        path: tagMan,
        name: 'tagMan',
        component: () => import('./SuperPages/PgTagMan.vue'),
        meta: { title: 'Tag Management' },
    },
    {
        path: aiModelInstructionMan,
        name: 'aiModelInstructionMan',
        component: () => import('./SuperPages/PgAiModelInstructionMan.vue'),
        meta: { title: 'AI Model Instructions' },
    },
    {
        path: whatsappMan,
        name: 'whatsappMan',
        component: () => import('./WhatsAppPages/PgWhatsApp.vue'),
        meta: { title: 'WhatsApp Inbox' },
    },
    {
        path: whatsappTemplateMan,
        name: 'whatsappTemplateMan',
        component: () => import('./WhatsAppPages/PgWhatsAppTemplate.vue'),
        meta: { title: 'WhatsApp Templates' },
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});

export const setDocumentTitle = (to: RouteLocationNormalized) => {
    try {
        const main = useMainStore();
        const routeTitle = to?.meta?.title
            ? String(to.meta.title as string)
            : to?.name
              ? String(to.name)
              : '';
        const appName = main?.appName || document.title.split(' - ').slice(-1)[0] || '';
        document.title = routeTitle ? `${routeTitle} - ${appName}` : appName;
    } catch {
        // silent fallback to preserve existing title
    }
};

// Keep document.title in sync with current route + app name
router.afterEach((to) => {
    setDocumentTitle(to);
});

export const useWebStore = defineStore('web', {
    state: () => ({
        /** Define route here because if not defined and get from XHR it will be race condition */
        /** WEB requests */
        loginPage: loginPage,
        authGetLogout: authGetLogout,
        dashboard: dashboard,
        profile: profile,
        serverHorizon: serverHorizon,
        routeAnalytics: routeAnalytics,
        serverLogs: serverLogs,
        userMan: userMan,
        roleMan: roleMan,
        passportMan: passportMan,
        divisionMan: divisionMan,
        tagMan: tagMan,
        aiModelInstructionMan: aiModelInstructionMan,
        whatsappMan: whatsappMan,
        whatsappTemplateMan: whatsappTemplateMan,
    }),
});
