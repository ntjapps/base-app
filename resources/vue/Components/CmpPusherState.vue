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

const isRecord = (v: unknown): v is Record<string, unknown> => v !== null && typeof v === 'object';

const showConnected = () => {
    connected.value = true;
    connecting.value = false;
    unavailable.value = false;
    echo.private('all').error((error: unknown) => {
        if (isRecord(error)) {
            const status = error.status;
            if (typeof status === 'number' && status >= 400 && status < 500) {
                console.error('Pusher error', error);
            }
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
    /** Ticking status for pusher */
    setInterval(() => {
        const echoNow = laravelEcho.value;
        if (!isRecord(echoNow)) {
            console.warn('Pusher echo is not initialized.');
            pusherState.value = 'unavailable';
            showUnavailable();
            return;
        }

        const connectorUnknown: unknown = (echoNow as unknown as { connector?: unknown }).connector;
        if (!isRecord(connectorUnknown)) {
            console.warn('Pusher connector is not an object.');
            pusherState.value = 'unavailable';
            showUnavailable();
        } else {
            const connector = connectorUnknown as Record<string, unknown>;
            const pusher = connector['pusher'];
            if (!isRecord(pusher)) {
                console.warn('Pusher is not available on the current connector.');
                pusherState.value = 'unavailable';
                showUnavailable();
            } else {
                const connection = (pusher as Record<string, unknown>)['connection'];
                if (!isRecord(connection) || typeof connection['state'] !== 'string') {
                    console.warn('Pusher connection is not available.');
                    pusherState.value = 'unavailable';
                    showUnavailable();
                } else {
                    pusherState.value = String(connection['state']);
                }
            }
        }

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
            <UButton size="xl" variant="ghost" icon="i-heroicons-arrow-path" class="animate-spin" />
        </UTooltip>
        <UTooltip v-if="unavailable" text="Unavailable">
            <UButton size="xl" variant="ghost" icon="i-heroicons-x-mark" />
        </UTooltip>
    </div>
</template>
