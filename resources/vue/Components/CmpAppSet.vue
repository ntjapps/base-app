<script setup lang="ts">
import { onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useMainStore, useEchoStore } from '../AppState';

import DynamicDialog from 'primevue/dynamicdialog';

const main = useMainStore();
const echo = useEchoStore();
const { laravelEcho } = storeToRefs(echo);
const { userId } = storeToRefs(main);

// eslint-disable-next-line no-undef
const toast = useToast();

const registerNotification = () => {
    if (userId.value !== null && userId.value !== undefined && userId.value !== '') {
        laravelEcho.leave('App.Models.User.' + userId.value);

        laravelEcho
            ?.private('App.Models.User.' + userId.value)
            .notification(
                (notification: {
                    severity: 'success' | 'info' | 'warning' | 'error' | undefined;
                    summary: string | undefined;
                    message: string | undefined;
                    life: number | undefined;
                }) => {
                    toast.add({
                        color: notification.severity,
                        title: notification.summary,
                        description: notification.message,
                    });
                },
            );
    }
};

onMounted(() => {
    registerNotification();
    main.spaCsrfToken();
    main.getNotificationList();
    main.init();
    main.browserSuppportCheck();
});
</script>

<template>
    <DynamicDialog />
</template>
