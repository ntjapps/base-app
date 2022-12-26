<script setup lang="ts">
import CmpHeader from "../Components/CmpHeader.vue";
import CmpFooter from "../Components/CmpFooter.vue";
</script>

<template>
    <div class="min-h-screen h-auto bg-slate-200">
        <CmpHeader />
        <div class="flex flex-col min-h-fit content-start">
            <slot />
        </div>
        <CmpFooter />
    </div>
</template>
