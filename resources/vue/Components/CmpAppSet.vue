<script setup lang="ts">
import { onMounted } from 'vue';
import { useMainStore } from '../AppState';

import DynamicDialog from 'primevue/dynamicdialog';
import Toast from 'primevue/toast';

const main = useMainStore();

onMounted(() => {
    main.spaCsrfToken();
    main.getNotificationList();
    main.init();
    main.browserSuppportCheck();
});
</script>

<template>
    <DynamicDialog />
    <Toast />
</template>
