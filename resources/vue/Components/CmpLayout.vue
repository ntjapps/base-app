<script setup lang="ts">
import CmpHeader from "../Components/CmpHeader.vue";
import CmpFooter from "../Components/CmpFooter.vue";
</script>

<template>
    <div class="content-container bg-base-100">
        <CmpHeader />
        <div class="content-child-container">
            <slot />
        </div>
        <CmpFooter />
    </div>
</template>
