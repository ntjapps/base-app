<script setup lang="ts">
import { ref, onBeforeMount } from "vue";
import { useMainStore } from "../AppState";

import ButtonVue from "primevue/button";
import TieredMenu from "primevue/tieredmenu";

const main = useMainStore();

const menu = ref<TieredMenu>();

const toggleMenu = (event: Event) => {
    if (menu.value !== undefined) {
        menu.value.toggle(event);
    }
};

onBeforeMount(() => {
    /** Ticking status for pusher */
    //setInterval(() => {
    //    this.pusherState = window.Echo.connector.pusher.connection.state;
    //    let EchoStatusElement = document.getElementById("EchoStatus");
    //    if (EchoStatusElement !== null) {
    //        switch (this.pusherState) {
    //            case "connecting":
    //                EchoStatusElement.className =
    //                    "pi pi-spin pi-spinner echo-connect-loading";
    //                break;
    //            case "connected":
    //                EchoStatusElement.className =
    //                    "pi pi-circle-fill echo-connect-connected";
    //                break;
    //            case "unavailable":
    //                EchoStatusElement.className =
    //                    "pi pi-circle-fill echo-connect-failed";
    //                break;
    //            case "failed":
    //                EchoStatusElement.className =
    //                    "pi pi-circle-fill echo-connect-failed";
    //                break;
    //            case "disconnected":
    //                EchoStatusElement.className =
    //                    "pi pi-spin pi-spinner echo-connect-loading";
    //                break;
    //            default:
    //                EchoStatusElement.className =
    //                    "pi pi-spin pi-spinner echo-connect-loading";
    //        }
    //    }
    //}, 1000);
});
</script>

<template>
    <div class="sticky top-0 w-full z-50 drop-shadow-xl">
        <div class="bg-indigo-800 py-3 px-5 flex flex-row">
            <div class="flex flex-row w-full">
                <ButtonVue
                    type="button"
                    icon="pi pi-bars"
                    label="Menu"
                    class="p-button-sm"
                    @click.prevent="toggleMenu"
                />
                <TieredMenu ref="menu" :model="main.menuItems" :popup="true" />
            </div>

            <div class="flex flex-row-reverse w-full">
                <div
                    v-if="main.browserSuppport"
                    class="flex flex-row-reverse w-full mt-1 mb-1"
                >
                    <!--button
                        id="EchoStatus"
                        class="pi pi-spin pi-spinner echo-connect-loading"
                    ></!--button>-->
                </div>
                <div
                    v-if="!main.browserSuppport"
                    class="flex flex-row-reverse w-full mt-1 mb-1"
                >
                    <ButtonVue
                        class="p-button-sm p-button-danger"
                        label="Browser Unsupported"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
