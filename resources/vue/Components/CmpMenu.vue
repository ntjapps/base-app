<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed } from 'vue';

import { useMainStore } from '../AppState';
import companyLogo from '../../images/Main Logo.webp';

const main = useMainStore();
const { appName, menuItems } = storeToRefs(main);

const activeValues = computed({
    get() {
        const e = main.expandedKeysMenu ?? {};
        return Object.keys(e).filter((k) => !!e[k]);
    },
    set(keys: string | string[] | Record<string, boolean>) {
        main.updateExpandedKeysMenu(keys);
    },
});
</script>

<template>
    <div class="h-full w-full p-3 md:p-4">
        <div class="flex h-full flex-col">
            <div class="mb-5 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                <div class="flex items-center gap-2.5">
                    <img :src="companyLogo" alt="Company logo" class="h-7 w-auto object-contain" />
                    <div class="min-w-0">
                        <div class="truncate text-xs font-semibold text-gray-500">NTJ</div>
                        <div class="truncate text-sm font-bold text-gray-900">{{ appName }}</div>
                    </div>
                </div>
            </div>

            <div class="w-full">
                <UNavigationMenu
                    v-model="activeValues"
                    :items="menuItems"
                    orientation="vertical"
                    class="data-[orientation=vertical]:min-w-full"
                />
            </div>
        </div>
    </div>
</template>
