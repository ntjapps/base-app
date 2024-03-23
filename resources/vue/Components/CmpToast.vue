<script setup lang="ts">
import { AxiosResponse } from "axios";
import { useToast } from "primevue/usetoast";

const toast = useToast();

export type ToastDisplay = {
    toastDisplay: (detailData: toastData) => void;
};

type toastData = {
    severity:
        | "success"
        | "info"
        | "warn"
        | "error"
        | "secondary"
        | "contrast"
        | undefined;
    summary: string | undefined;
    detail: string | undefined;
    life: number | undefined;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    response?: AxiosResponse<any, any> | any;
};

const toastDisplay = (detailData: toastData) => {
    if (detailData.severity === "error") {
        const error = detailData.response;

        if (typeof error.response === "undefined") {
            toast.add({
                severity: "error",
                summary: "Unknown Error",
                detail: "Please contact the administrator",
                life: 10000,
            });
        } else {
            if (error.response.status === 500) {
                toast.add({
                    severity: "error",
                    summary: "Server Error",
                    detail: "Please contact the administrator",
                    life: 10000,
                });
            } else if (error.response.status === 401) {
                toast.add({
                    severity: "error",
                    summary: "Unauthorized",
                    detail: "Action not authorized.",
                    life: 10000,
                });
            } else if (error.response.status === 403) {
                toast.add({
                    severity: "error",
                    summary: "Forbidden",
                    detail: "Access denied.",
                    life: 10000,
                });
            } else if (error.response.status === 404) {
                toast.add({
                    severity: "error",
                    summary: "Not Found",
                    detail: "Resource not found.",
                    life: 10000,
                });
            } else if (error.response.data.errors === undefined) {
                toast.add({
                    severity: "error",
                    summary: "Unknown Error",
                    detail:
                        "Please contact the administrator, status code: " +
                        error.response.status,
                    life: 10000,
                });
            } else {
                Object.values(error.response.data.errors).forEach((value) => {
                    const objVal = value as Array<string>;
                    toast.add({
                        severity: "error",
                        summary: error.response.data.message,
                        detail: objVal.toString(),
                        life: 10000,
                    });
                });
            }
        }
    } else {
        toast.add({
            severity: detailData.severity,
            summary: detailData.summary,
            detail: detailData.detail,
            life: detailData.life ?? 5000,
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
