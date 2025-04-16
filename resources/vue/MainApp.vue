<script setup lang="ts">
/* eslint-disable vue/no-undef-components */
import CmpAppSet from './Components/CmpAppSet.vue';

defineProps<{
    appName: string;
    greetings: string;
    expandedKeysProps: string;
}>();
</script>

<template>
    <UApp :toaster="{ position: 'top-right' }">
        <CmpAppSet />
        <RouterView
            :appName="appName"
            :greetings="greetings"
            :expandedKeysProps="expandedKeysProps"
        />
    </UApp>
</template>
