<script setup lang="ts">
import CmpHeader from '../Components/CmpHeader.vue';
import CmpFooter from '../Components/CmpFooter.vue';
import CmpMenu from '../Components/CmpMenu.vue';
import CmpMobileMenu from '../Components/CmpMobileMenu.vue';
</script>

<template>
    <div class="flex flex-row w-full min-h-screen">
        <div class="md:flex hidden flex-grow-0 min-h-full w-1/6 bg-surface-200">
            <CmpMenu />
        </div>
        <div class="flex md:hidden">
            <CmpMobileMenu />
        </div>
        <div class="flex flex-col bg-surface-100 w-full">
            <CmpHeader />
            <div>
                <slot />
            </div>
            <CmpFooter />
        </div>
    </div>
</template>
