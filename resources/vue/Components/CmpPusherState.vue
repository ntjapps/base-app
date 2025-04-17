<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useEchoStore } from '../AppState';

import IconChartBar from '../Icons/IconChartBar.vue';

const pusherState = ref<string>('connecting');
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
        <UTooltip v-if="connected" text="Connected">
            <UButton size="xl" variant="ghost">
                <IconChartBar />
            </UButton>
        </UTooltip>
        <UTooltip v-if="connecting" text="Connecting">
            <UButton size="xl" variant="ghost"><i class="pi pi-spinner animate-spin" /></UButton>
        </UTooltip>
        <UTooltip v-if="unavailable" text="Unavailable">
            <UButton size="xl" variant="ghost"><i class="pi pi-times" /></UButton>
        </UTooltip>
    </div>
</template>
