import { createApp, App } from "vue";
import { createPinia, Pinia } from "pinia";
const pinia: Pinia = createPinia();
import PrimeVue from "primevue/config";

/** Vue router needed for navigation menu */
import { router } from "./AppRouter";

/** Primevue Globals */
import DialogService from "primevue/dialogservice";

// Mount Application Instances
const MainApp: App<Element> = createApp({})
    .use(router)
    .use(pinia)
    .use(PrimeVue)
    .use(DialogService);

/** Global Composenent / Page Registration */
import CmpAppSet from "./Components/CmpAppSet.vue";
MainApp.component("CmpAppSet", CmpAppSet);

router.isReady().then(() => {
    MainApp.mount("#app");
});
