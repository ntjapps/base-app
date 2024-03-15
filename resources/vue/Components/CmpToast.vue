<script setup lang="ts">
import { useToast } from "primevue/usetoast";

const toast = useToast();

const toastSuccess = (detailData: string) =>
    toast.add({
        severity: "success",
        summary: "Success",
        detail: detailData,
        life: 3000,
    });

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const toastError = (error: any) => {
    if (error.response.status === 500) {
        toast.add({
            severity: "error",
            summary: "Server Error",
            detail: "Please contact the administrator",
            life: 30000,
        });
    } else if (error.response.status === 401) {
        toast.add({
            severity: "error",
            summary: "Unauthorized",
            detail: "Action not authorized.",
            life: 30000,
        });
    } else if (error.response.status === 403) {
        toast.add({
            severity: "error",
            summary: "Forbidden",
            detail: "Access denied.",
            life: 30000,
        });
    } else if (error.response.status === 404) {
        toast.add({
            severity: "error",
            summary: "Not Found",
            detail: "Resource not found.",
            life: 30000,
        });
    } else if (error.response.data.errors === undefined) {
        toast.add({
            severity: "error",
            summary: "Unknown Error",
            detail:
                "Please contact the administrator, status code: " +
                error.response.status,
            life: 30000,
        });
    } else {
        Object.values(error.response.data.errors).forEach((value) => {
            const objVal = value as Array<string>;
            toast.add({
                severity: "error",
                summary: error.response.data.message,
                detail: objVal.toString(),
                life: 30000,
            });
        });
    }
};

defineExpose({
    toastSuccess,
    toastError,
});
</script>

<template>
    <slot />
</template>
