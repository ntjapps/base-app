<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useEchoStore } from '../AppState';

import { useMainStore } from '../AppState';

import Button from 'primevue/button';

import IconChartBar from '../Icons/IconChartBar.vue';

const pusherState = ref<string>('connecting');
const connected = ref<boolean>(false);
const connecting = ref<boolean>(true);
const unavailable = ref<boolean>(false);
const echoStore = useEchoStore();
const { laravelEcho } = storeToRefs(echoStore);
const echo = laravelEcho.value;

const main = useMainStore();
const { appName } = storeToRefs(main);

const showConnected = () => {
    connected.value = true;
    connecting.value = false;
    unavailable.value = false;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    echo.private('all').error((error: any) => {
        if (error.status >= 400 && error.status < 500) {
            console.error('Pusher error', error);
        }
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

onMounted(() => {
    //echo.connector.options.auth.headers["Authorization"] =
    //"Bearer " + secure.apiToken;
    /** Ticking status for pusher */
    setInterval(() => {
        pusherState.value = echo.connector.pusher.connection.state;
        switch (pusherState.value) {
            case 'connecting':
                showConnecting();
                break;
            case 'connected':
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
    <div class="flex mx-2 my-auto">
        <Button v-if="connected" v-tooltip.bottom="appName + ' Connected'" text>
            <IconChartBar />
        </Button>
        <Button
            v-if="connecting"
            v-tooltip.bottom="appName + ' Connecting'"
            text
            aria-label="Connecting"
            icon="pi pi-spin pi-spinner"
        />
        <Button
            v-if="unavailable"
            v-tooltip.bottom="appName + ' Disconnected'"
            text
            aria-label="Unavailable"
            icon="pi pi-times"
        />
    </div>
</template>
