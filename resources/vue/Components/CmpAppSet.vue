<script setup lang="ts">
import { onBeforeMount } from "vue";
import { useMainStore } from "../AppState";

import DynamicDialog from "primevue/dynamicdialog";

const main = useMainStore();

onBeforeMount(() => {
    main.init();
    main.browserSuppportCheck();
    main.spaCsrfToken();
});
</script>

<template>
    <DynamicDialog />
</template>
