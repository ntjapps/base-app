<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useMainStore, useEchoStore } from '../AppState';
import { api } from '../AppAxios';
import CmpToast from './CmpToast.vue';

const main = useMainStore();
const echo = useEchoStore();
const { laravelEcho, workerBackend } = storeToRefs(echo);

// eslint-disable-next-line no-undef
const toast = useToast();
const prevId = ref<string | null>(null);
const toastRef = ref<InstanceType<typeof CmpToast> | null>(null);

// Expose worker backend info for other components to use
const workerBackendInfo = computed(() => main.workerBackend);

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
    type ToastSetter = { setToastDisplay?: (fn: (data: unknown) => void) => void };
    const hasSetter = (obj: unknown): obj is ToastSetter =>
        typeof obj === 'object' &&
        obj !== null &&
        'setToastDisplay' in (obj as Record<string, unknown>);
    if (
        toastRef.value?.toastDisplay &&
        hasSetter(api) &&
        typeof api.setToastDisplay === 'function'
    ) {
        api.setToastDisplay(toastRef.value.toastDisplay);
    }

    registerNotification();
    main.spaCsrfToken();
    main.init();
    main.browserSuppportCheck();
});

// Export worker backend info
defineExpose({
    workerBackendInfo,
});
</script>

<template>
    <CmpToast ref="toastRef" />
    <slot />
</template>
