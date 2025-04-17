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
};

const toastDisplay = (detailData: toastData) => {
    if (detailData.severity === 'error') {
        const error = detailData.response;

        if (typeof error.response === 'undefined') {
            toast.add({
                color: 'error',
                title: 'Unknown Error',
                description: 'Please contact the administrator',
            });
        } else {
            if (error.response.status === 500) {
                toast.add({
                    color: 'error',
                    title: 'Server Error',
                    description: 'Please contact the administrator',
                });
            } else if (error.response.status === 401) {
                toast.add({
                    color: 'error',
                    title: 'Unauthorized',
                    description: 'Action not authorized.',
                });
            } else if (error.response.status === 403) {
                toast.add({
                    color: 'error',
                    title: 'Forbidden',
                    description: 'Access denied.',
                });
            } else if (error.response.status === 404) {
                toast.add({
                    color: 'error',
                    title: 'Not Found',
                    description: 'Resource not found.',
                });
            } else if (error.response.data.errors === undefined) {
                toast.add({
                    color: 'error',
                    title: 'Unknown Error',
                    description:
                        'Please contact the administrator, status code: ' + error.response.status,
                });
            } else {
                Object.values(error.response.data.errors).forEach((value) => {
                    const objVal = value as Array<string>;
                    toast.add({
                        color: 'error',
                        title: error.response.data.message,
                        description: objVal.toString(),
                    });
                });
            }
        }
    } else {
        toast.add({
            color: detailData.severity,
            title: detailData.title,
            description: detailData.detail,
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
