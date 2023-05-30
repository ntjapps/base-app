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
    if (error.response.data.errors === undefined) {
        toast.add({
            severity: "error",
            summary: "Unknown Error",
            detail: error.response.toString(),
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
