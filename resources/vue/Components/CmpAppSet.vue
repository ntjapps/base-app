<script setup lang="ts">
import { onBeforeMount } from "vue";
import { useMainStore } from "../AppState";

import DynamicDialog from "primevue/dynamicdialog";
import Toast from "primevue/toast";

const main = useMainStore();

onBeforeMount(() => {
    main.spaCsrfToken();
    main.init();
    main.browserSuppportCheck();
});
</script>

<template>
    <DynamicDialog />
    <Toast />
</template>
