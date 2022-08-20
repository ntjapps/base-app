import { createApp } from 'vue/dist/vue.esm-bundler.js'
import { createPinia } from 'pinia'
const pinia: any = createPinia()
import PrimeVue from 'primevue/config'
import { router } from './vue-router'

// Mount Application Instances
import App from '../vue/App.vue'
const MainApp: any = createApp(App)
MainApp.use(router)
MainApp.use(pinia)
MainApp.use(PrimeVue)
MainApp.mount('#app')