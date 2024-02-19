import { createApp, App } from "vue";
import { createPinia, Pinia } from "pinia";
const pinia: Pinia = createPinia();
import PrimeVue from "primevue/config";
import PrimeTailwind from "./presets/custom";

import * as Sentry from "@sentry/vue";

/** Vue router needed for navigation menu */
import { router } from "./AppRouter";

/** Primevue Globals */
import DialogService from "primevue/dialogservice";
import ToastService from "primevue/toastservice";
import Tooltip from "primevue/tooltip";

// Mount Application Instances
const MainApp: App<Element> = createApp({})
    .use(router)
    .use(pinia)
    .use(PrimeVue, {
        unstyled: true,
        pt: PrimeTailwind,
        ptOptions: { mergeProps: true },
    })
    .use(DialogService)
    .use(ToastService)
    .directive("tooltip", Tooltip);

/** Global Composenent / Page Registration */
import CmpAppSet from "./Components/CmpAppSet.vue";
MainApp.component("CmpAppSet", CmpAppSet);

/** Sentry iniitialization */
Sentry.init({
    app: MainApp,
    dsn: import.meta.env.VITE_SENTRY_DSN,
    integrations: [
        Sentry.browserTracingIntegration({ router }),
        Sentry.replayIntegration(),
    ],

    // Performance Monitoring
    tracesSampleRate: 0.01, //  Capture 1% of the transactions
    // Session Replay
    replaysSessionSampleRate: 0.01, // This sets the sample rate at 1%. You may want to change it to 100% while in development and then sample at a lower rate in production.
    replaysOnErrorSampleRate: 1.0, // If you're not already sampling the entire session, change the sample rate to 100% when sampling sessions where errors occur.
});

router.isReady().then(() => {
    MainApp.mount("#app");
});
