<script setup lang="ts">
import { ref, onBeforeMount } from "vue";

import ButtonVue from "primevue/button";

const pusherState = ref<string>("connecting");
const connected = ref<boolean>(false);
const connecting = ref<boolean>(true);
const unavailable = ref<boolean>(false);

const showConnected = () => {
    connected.value = true;
    connecting.value = false;
    unavailable.value = false;
    window.Echo.private("all");
};

const showConnecting = () => {
    connected.value = false;
    connecting.value = true;
    unavailable.value = false;
};

const showUnavailable = () => {
    connected.value = false;
    connecting.value = false;
    unavailable.value = true;
};

onBeforeMount(() => {
    //window.Echo.connector.options.auth.headers["Authorization"] =
    //"Bearer " + secure.apiToken;
    /** Ticking status for pusher */
    setInterval(() => {
        pusherState.value = window.Echo.connector.pusher.connection.state;
        switch (pusherState.value) {
            case "connecting":
                showConnecting();
                break;
            case "connected":
                showConnected();
                break;
            default:
                showUnavailable();
                break;
        }
    }, 1000);
});
</script>

<template>
    <ButtonVue
        v-if="connected"
        class="p-button-sm p-button-success text-xs"
        label="Connected"
        icon="pi pi-bell"
    />
    <ButtonVue
        v-if="connecting"
        class="p-button-sm p-button-warning text-xs"
        label="Connecting"
        icon="pi pi-spin pi-spinner"
    />
    <ButtonVue
        v-if="unavailable"
        class="p-button-sm p-button-danger text-xs"
        label="Unavailable"
        icon="pi pi-times"
    />
</template>
