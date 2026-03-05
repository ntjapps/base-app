<script setup lang="ts">
import { onMounted } from 'vue';
import { api } from '../AppAxios';
import { useMainStore } from '../AppState';
import { storeToRefs } from 'pinia';
import companyLogo from '../../images/Main Logo.webp';

const main = useMainStore();
const { appName } = storeToRefs(main);

onMounted(async () => {
    try {
        await api.postLogout({ noToast: true });
    } catch (error) {
        console.error('Logout failed:', error);
    } finally {
        window.location.href = '/';
    }
});
</script>

<template>
    <div class="flex min-h-screen w-full items-center justify-center bg-gray-300 p-4">
        <div class="w-full max-w-sm">
            <div class="rounded-2xl bg-white px-7 py-10 shadow-[0_16px_36px_rgba(0,0,0,0.16)]">
                <div class="mb-3 flex justify-center">
                    <img :src="companyLogo" alt="Company logo" class="h-10 w-auto object-contain" />
                </div>
                <div class="mb-7 text-center text-xs font-semibold text-gray-700">
                    {{ appName }}
                </div>
                <div class="flex flex-col items-center justify-center gap-4 text-gray-700">
                    <UIcon name="i-heroicons-arrow-path" class="animate-spin text-5xl" />
                    <div class="text-xl font-semibold">Logging out...</div>
                </div>
            </div>
        </div>
    </div>
</template>
