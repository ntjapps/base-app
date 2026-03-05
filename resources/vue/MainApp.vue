<script setup lang="ts">
/* eslint-disable vue/no-undef-components */
import CmpAppSet from './Components/CmpAppSet.vue';
</script>

<template>
    <UApp :toaster="{ position: 'top-right' }">
        <CmpAppSet />
        <RouterView />
    </UApp>
</template>
