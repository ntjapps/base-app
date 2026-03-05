<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useMainStore } from '../AppState';

import CmpPusherState from './CmpPusherState.vue';
import CmpClearCacheButton from './CmpClearCacheButton.vue';

const main = useMainStore();
const { browserSuppport, menuVisible } = storeToRefs(main);

const toggleMenu = () => {
    main.$patch({
        menuVisible: !menuVisible.value,
    });
};
</script>

<template>
    <div class="header-container border-b border-gray-200 bg-white/80 px-3 py-2 md:px-5 md:py-3">
        <div class="flex flex-row">
            <div class="flex w-full flex-row items-center">
                <div class="flex lg:hidden">
                    <UButton
                        size="md"
                        color="primary"
                        icon="i-heroicons-bars-3"
                        @click="toggleMenu"
                    />
                </div>
            </div>

            <div class="flex w-full justify-end">
                <div class="my-auto flex w-full justify-end gap-2">
                    <CmpClearCacheButton />
                    <CmpPusherState v-if="browserSuppport" />
                    <UButton
                        v-if="!browserSuppport"
                        size="md"
                        color="error"
                        icon="i-heroicons-x-mark"
                        >Browser Unsupported</UButton
                    >
                </div>
            </div>
        </div>
    </div>
</template>
