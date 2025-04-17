<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useMainStore } from '../AppState';

import CmpPusherState from './CmpPusherState.vue';
import CmpClearCacheButton from './CmpClearCacheButton.vue';

const main = useMainStore();
const { browserSuppport, menuVisible } = storeToRefs(main);

const openMenu = () => {
    main.$patch({
        menuVisible: !menuVisible.value,
    });
};
</script>

<template>
    <div class="header-container">
        <div class="bg-surface-300 py-3 px-5 flex flex-row">
            <div class="flex flex-row w-full">
                <div class="flex md:hidden">
                    <UButton size="lg" color="primary" @click="openMenu">
                        <i class="pi pi-bars" />
                    </UButton>
                </div>
            </div>

            <div class="flex justify-end w-full">
                <div class="flex justify-end w-full my-auto">
                    <CmpClearCacheButton />
                    <CmpPusherState v-if="browserSuppport" />
                    <UButton v-if="!browserSuppport" size="xl" color="error"
                        ><i class="pi pi-times" />Browser Unsupported</UButton
                    >
                </div>
            </div>
        </div>
    </div>
</template>
