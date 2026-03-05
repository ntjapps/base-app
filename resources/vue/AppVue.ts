import { createApp, App } from 'vue';
import { createPinia, Pinia } from 'pinia';
const pinia: Pinia = createPinia();
import ui from '@nuxt/ui/vue-plugin';
import { createHead } from '@unhead/vue/client';
const head = createHead();

/** Vue router needed for navigation menu */

import { router } from './AppRouter.ts';

// Mount Application Instances
const VueApp: App<Element> = createApp({}).use(router).use(pinia).use(ui).use(head);

/** Initialize Echo after pinia is available */
import { useEchoStore } from './AppState';
const echoStore = useEchoStore();
echoStore.initEcho();

/** Global Composenent / Page Registration */
import MainApp from './MainApp.vue';
VueApp.component('MainApp', MainApp);

/** Add Sentry */
import * as Sentry from '@sentry/vue';

Sentry.init({
    app: VueApp,
    dsn: import.meta.env.VITE_SENTRY_DSN ?? '',

    integrations: [Sentry.browserTracingIntegration()],
    tracesSampleRate: 0.0,
});

router.isReady().then(() => {
    VueApp.mount('#app');
});
