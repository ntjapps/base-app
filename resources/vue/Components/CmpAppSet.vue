<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useMainStore, useEchoStore } from '../AppState';
import { api } from '../AppAxios';
import CmpToast from './CmpToast.vue';

const main = useMainStore();
const echo = useEchoStore();
const { laravelEcho } = storeToRefs(echo);

// eslint-disable-next-line no-undef
const toast = useToast();
const currentRoute = useRoute();
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
                            // Prefer using the CmpToast normalization so icons/defaults are applied
                            if (toastRef.value?.toastDisplay) {
                                toastRef.value.toastDisplay({
                                    severity: notification.severity,
                                    title: notification.summary,
                                    detail: notification.message,
                                });
                            } else {
                                // Fallback to adding with an explicit icon
                                toast.add({
                                    color: notification.severity,
                                    title: notification.summary,
                                    description: notification.message,
                                    icon: 'i-lucide-bell-ring',
                                });
                            }
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

    // Initialize app constants and ensure document.title is updated when app name becomes available
    main.init().then(() => {
        try {
            const current = currentRoute;
            const routeTitle = current?.meta?.title
                ? String(current.meta.title as string)
                : current?.name
                  ? String(current.name)
                  : '';
            const appName = main.appName || document.title.split(' - ').slice(-1)[0] || '';
            document.title = routeTitle ? `${routeTitle} - ${appName}` : appName;
        } catch {
            // ignore
        }
    });

    main.browserSuppportCheck();
});
</script>

<template>
    <CmpToast ref="toastRef" />
    <slot />
</template>
