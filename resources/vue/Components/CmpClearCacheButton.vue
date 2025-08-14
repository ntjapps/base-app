<script setup lang="ts">
import { ref } from 'vue';
import { api } from '../AppAxios';
import CmpToast from './CmpToast.vue';

const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);

const postClearCache = async () => {
    try {
    await api.postClearAppCache();
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    }
};
</script>

<template>
    <div class="flex mx-2">
        <CmpToast ref="toastchild" />
        <UTooltip text="Clear Cache">
            <UButton size="xl" variant="ghost" @click="postClearCache">
                <i class="pi pi-fw pi-sync" />
            </UButton>
        </UTooltip>
    </div>
</template>
