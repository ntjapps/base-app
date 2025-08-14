<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useMainStore, useEchoStore } from '../AppState';
import { api } from '../AppAxios';
import CmpToast from './CmpToast.vue';

const main = useMainStore();
const echo = useEchoStore();
const { laravelEcho } = storeToRefs(echo);

// eslint-disable-next-line no-undef
const toast = useToast();
const prevId = ref<string | null>(null);
const toastRef = ref<InstanceType<typeof CmpToast> | null>(null);

const registerNotification = () => {
    main.$subscribe((mutation) => {
        if (mutation.type === 'patch object') {
            if (
                mutation.payload.userId !== null &&
                mutation.payload.userId !== undefined &&
                mutation.payload.userId !== ''
            ) {
                if (prevId.value !== null) {
                    laravelEcho.value.leave('App.Models.User.' + prevId.value);
                }

                laravelEcho.value
                    ?.private('App.Models.User.' + mutation.payload.userId)
                    .notification(
                        (notification: {
                            severity: 'success' | 'info' | 'warning' | 'error' | undefined;
                            summary: string | undefined;
                            message: string | undefined;
                        }) => {
                            toast.add({
                                color: notification.severity,
                                title: notification.summary,
                                description: notification.message,
                            });
                        },
                    );

                prevId.value = mutation.payload.userId;
            }
        }
    });
};

onMounted(() => {
    // Register global API toast handler once
    if (toastRef.value?.toastDisplay) {
        api.setToastDisplay(toastRef.value.toastDisplay);
    }

    registerNotification();
    main.spaCsrfToken();
    main.init();
    main.browserSuppportCheck();
});
</script>

<template>
    <CmpToast ref="toastRef" />
    <slot />
</template>
