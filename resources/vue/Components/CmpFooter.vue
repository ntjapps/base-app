<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useMainStore } from '../AppState';

const main = useMainStore();
const { appName } = storeToRefs(main);
const year = new Date().getFullYear();
</script>

<template>
    <div class="mt-auto border-t border-gray-200 bg-white/80 p-2 md:p-2.5">
        <div class="footer pl-2 md:pl-2.5 text-xs text-gray-500 space-y-1">
            <div>&copy; {{ year }} {{ appName }}. All rights reserved.</div>
        </div>
    </div>
</template>
