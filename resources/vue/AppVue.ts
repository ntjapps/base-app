import { createApp, App } from 'vue';
import { createPinia, Pinia } from 'pinia';
const pinia: Pinia = createPinia();
import PrimeVue from 'primevue/config';
import ui from '@nuxt/ui/vue-plugin';

/** Vue router needed for navigation menu */
import { router } from './AppRouter.ts';

/** Primevue Globals */
import DialogService from 'primevue/dialogservice';
import Tooltip from 'primevue/tooltip';

// Mount Application Instances
const VueApp: App<Element> = createApp({})
    .use(router)
    .use(pinia)
    .use(ui)
    .use(PrimeVue, {
        theme: 'none',
    })
    .use(DialogService)
    .directive('tooltip', Tooltip);

/** Global Composenent / Page Registration */
import MainApp from './MainApp.vue';
VueApp.component('MainApp', MainApp);

/** Add Sentry */
import * as Sentry from '@sentry/vue';

Sentry.init({
    app: VueApp,
    dsn: import.meta.env.VITE_SENTRY_DSN ?? '',

    integrations: [Sentry.browserTracingIntegration({ router })],
});

router.isReady().then(() => {
    VueApp.mount('#app');
});
