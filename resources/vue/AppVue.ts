import { createApp, App } from "vue";
import { createPinia, Pinia } from "pinia";
const pinia: Pinia = createPinia();
import PrimeVue from "primevue/config";
import { usePassThrough } from "primevue/passthrough";
import Tailwind from "primevue/passthrough/tailwind";

/** Vue router needed for navigation menu */
import { router } from "./AppRouter";

/** Primevue Globals */
import DialogService from "primevue/dialogservice";
import ToastService from "primevue/toastservice";
import Tooltip from "primevue/tooltip";

const CustomTailwind = usePassThrough(Tailwind, {
    mergeSections: true,
    mergeProps: false,
});

// Mount Application Instances
const MainApp: App<Element> = createApp({})
    .use(router)
    .use(pinia)
    .use(PrimeVue, { ripple: true, unstyled: true, pt: CustomTailwind })
    .use(DialogService)
    .use(ToastService)
    .directive("tooltip", Tooltip);

/** Global Composenent / Page Registration */
import CmpAppSet from "./Components/CmpAppSet.vue";
MainApp.component("CmpAppSet", CmpAppSet);

router.isReady().then(() => {
    MainApp.mount("#app");
});
