<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed } from 'vue';

import { useMainStore } from '../AppState';
import companyLogo from '../../images/Main Logo.webp';

const main = useMainStore();
const { appName, menuItems, menuVisible } = storeToRefs(main);

const closeMenu = () => {
    main.$patch({
        menuVisible: false,
    });
};

const activeValues = computed({
    get() {
        const e = main.expandedKeysMenu ?? {};
        const keys = Object.keys(e).filter((k) => !!e[k]);
        // NavigationMenu in horizontal mode expects a single value (string)
        return keys.length ? String(keys[0]) : '';
    },
    set(key: string | string[] | Record<string, boolean>) {
        // Accept either empty string (clear), or single key
        if (!key) {
            main.updateExpandedKeysMenu('');
            return;
        }
        if (Array.isArray(key)) {
            main.updateExpandedKeysMenu(key);
            return;
        }
        if (typeof key === 'string') {
            main.updateExpandedKeysMenu(key);
            return;
        }

        main.updateExpandedKeysMenu(key as Record<string, boolean>);
    },
});
</script>

<template>
    <div>
        <div
            v-show="menuVisible"
            class="fixed inset-0 z-40 bg-black/30 lg:hidden"
            @click="closeMenu"
        />

        <div
            v-show="menuVisible"
            class="fixed inset-y-0 left-0 z-50 w-72 border-r border-gray-200 bg-gray-100 p-4 shadow-lg lg:hidden"
        >
            <div
                class="mb-5 flex items-center justify-between rounded-xl border border-gray-200 bg-white p-3"
            >
                <div class="flex items-center gap-2.5">
                    <img :src="companyLogo" alt="Company logo" class="h-7 w-auto object-contain" />
                    <div class="min-w-0">
                        <div class="truncate text-xs font-semibold text-gray-500">NTJ</div>
                        <div class="truncate text-sm font-bold text-gray-900">{{ appName }}</div>
                    </div>
                </div>

                <UButton size="sm" color="neutral" icon="i-heroicons-x-mark" @click="closeMenu" />
            </div>

            <UNavigationMenu
                v-model="activeValues"
                :items="menuItems"
                orientation="vertical"
                class="data-[orientation=vertical]:min-w-full"
            />
        </div>
    </div>
</template>
