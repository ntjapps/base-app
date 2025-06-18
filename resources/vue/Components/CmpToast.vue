<script setup lang="ts">
import { AxiosResponse } from 'axios';

// eslint-disable-next-line no-undef
const toast = useToast();

export type ToastDisplay = {
    toastDisplay: (detailData: toastData) => void;
};

type toastData = {
    severity: 'success' | 'info' | 'warning' | 'error' | undefined;
    title: string | undefined;
    detail: string | undefined;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    response?: AxiosResponse<any, any> | any;
    icon?: string | undefined;
};

const toastDisplay = (detailData: toastData) => {
    if (detailData.severity === 'error' && detailData.response) {
        const error = detailData.response;

        if (typeof error.response === 'undefined') {
            toast.add({
                color: 'error',
                title: 'Unknown Error',
                description: 'Please contact the administrator',
                icon: 'i-lucide-ban',
            });
        } else {
            if (error.response.status === 500) {
                toast.add({
                    color: 'error',
                    title: 'Server Error',
                    description: 'Please contact the administrator',
                    icon: 'i-lucide-ban',
                });
            } else if (error.response.status === 401) {
                toast.add({
                    color: 'error',
                    title: 'Unauthorized',
                    description: 'Action not authorized.',
                    icon: 'i-lucide-ban',
                });
            } else if (error.response.status === 403) {
                toast.add({
                    color: 'error',
                    title: 'Forbidden',
                    description: 'Access denied.',
                    icon: 'i-lucide-ban',
                });
            } else if (error.response.status === 404) {
                toast.add({
                    color: 'error',
                    title: 'Not Found',
                    description: 'Resource not found.',
                    icon: 'i-lucide-ban',
                });
            } else if (error.response.data.errors === undefined) {
                toast.add({
                    color: 'error',
                    title: 'Unknown Error',
                    description:
                        'Please contact the administrator, status code: ' + error.response.status,
                    icon: 'i-lucide-ban',
                });
            } else {
                Object.values(error.response.data.errors).forEach((value) => {
                    const objVal = value as Array<string>;
                    toast.add({
                        color: 'error',
                        title: error.response.data.message,
                        description: objVal.toString(),
                        icon: 'i-lucide-ban',
                    });
                });
            }
        }
    } else {
        toast.add({
            color: detailData.severity,
            title: detailData.title,
            description: detailData.detail,
            icon: 'i-lucide-bell-ring',
        });
    }
};

defineExpose({
    toastDisplay,
});
</script>

<template>
    <slot />
</template>
