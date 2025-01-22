<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useMainStore } from '../AppState';

import CmpPusherState from './CmpPusherState.vue';
import CmpClearCacheButton from './CmpClearCacheButton.vue';

import Button from 'primevue/button';

const main = useMainStore();
const { browserSuppport } = storeToRefs(main);
</script>

<template>
    <div class="header-container">
        <div class="bg-base-300 py-3 px-5 flex flex-row">
            <div class="flex flex-row w-full" />

            <div class="flex justify-end w-full">
                <div class="flex justify-end w-full my-auto">
                    <CmpClearCacheButton />
                    <CmpPusherState v-if="browserSuppport" />
                    <Button
                        v-if="!browserSuppport"
                        icon="pi pi-times"
                        label="Browser Unsupported"
                        severity="danger"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
