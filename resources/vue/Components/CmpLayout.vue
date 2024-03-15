<script setup lang="ts">
import CmpHeader from "../Components/CmpHeader.vue";
import CmpFooter from "../Components/CmpFooter.vue";
import CmpMenu from "../Components/CmpMenu.vue";
</script>

<template>
    <div class="flex flex-row w-full">
        <div class="flex flex-grow-0 min-h-full w-1/6 bg-base-200">
            <CmpMenu />
        </div>
        <div class="content-container bg-base-100 w-full">
            <CmpHeader />
            <div class="content-child-container">
                <slot />
            </div>
            <CmpFooter />
        </div>
    </div>
</template>
