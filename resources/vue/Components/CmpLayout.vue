<script setup lang="ts">
import CmpHeader from '../Components/CmpHeader.vue';
import CmpFooter from '../Components/CmpFooter.vue';
import CmpMenu from '../Components/CmpMenu.vue';
import CmpMobileMenu from '../Components/CmpMobileMenu.vue';
</script>

<template>
    <div class="min-h-screen w-full bg-gray-200">
        <div class="flex min-h-screen w-full overflow-hidden bg-gray-100">
            <div class="hidden w-64 shrink-0 border-r border-gray-200 bg-gray-100 lg:block">
                <CmpMenu />
            </div>

            <div class="flex min-w-0 flex-1 flex-col">
                <CmpHeader />
                <div class="flex-1 overflow-auto p-3 md:p-5">
                    <slot />
                </div>
                <CmpFooter />
            </div>
        </div>

        <CmpMobileMenu />
    </div>
</template>
