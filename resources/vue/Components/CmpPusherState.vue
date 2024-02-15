<script setup lang="ts">
import { ref, onBeforeMount } from "vue";
import { storeToRefs } from "pinia";
import { useEchoStore } from "../AppState";

const pusherState = ref<string>("connecting");
const connected = ref<boolean>(false);
const connecting = ref<boolean>(true);
const unavailable = ref<boolean>(false);
const echoStore = useEchoStore();
const { laravelEcho } = storeToRefs(echoStore);
const echo = laravelEcho.value;

const showConnected = () => {
    connected.value = true;
    connecting.value = false;
    unavailable.value = false;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    echo.private("all").error((error: any) => {
        if (error.status >= 400 && error.status < 500) {
            console.error("Pusher error", error);
        }
        window.location.reload();
    });
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
    //echo.connector.options.auth.headers["Authorization"] =
    //"Bearer " + secure.apiToken;
    /** Ticking status for pusher */
    setInterval(() => {
        pusherState.value = echo.connector.pusher.connection.state;
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
    }, 500);
});
</script>

<template>
    <div class="flex mx-2">
        <button v-if="connected" class="btn btn-success text-xs">
            <i class="ico ico-chart-bar w-5 h-5" />
            <span class="m-1">Connected</span>
        </button>
        <button v-if="connecting" class="btn btn-warning loading text-xs">
            <span class="m-1">Connecting</span>
        </button>
        <button v-if="unavailable" class="btn btn-error text-xs">
            <i class="pi pi-times mr-1" />
            <span class="m-1">Unavailable</span>
        </button>
    </div>
</template>
