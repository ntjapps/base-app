<script setup lang="ts">
import { storeToRefs } from "pinia";
import { useMainStore } from "../AppState";

import CmpPusherState from "./CmpPusherState.vue";
import CmpClearCacheButton from "./CmpClearCacheButton.vue";

const main = useMainStore();
const { browserSuppport } = storeToRefs(main);

const props = defineProps({
    pageTitle: {
        type: String,
        default: "",
    },
});
</script>

<template>
    <div class="header-container">
        <div class="bg-base-300 py-3 px-5 flex flex-row">
            <div class="flex flex-row w-full">
                <div class="my-auto ml-4 font-bold">
                    {{ props.pageTitle }}
                </div>
            </div>

            <div class="flex flex-row-reverse w-full">
                <div
                    v-if="browserSuppport"
                    class="flex flex-row-reverse w-full my-auto"
                >
                    <CmpClearCacheButton />
                    <CmpPusherState />
                </div>
                <div
                    v-if="!browserSuppport"
                    class="flex flex-row-reverse w-full my-auto"
                >
                    <CmpClearCacheButton />
                    <button class="btn btn-sm btn-error">
                        <i class="pi pi-times m-1" />
                        <span class="m-1">Browser Unsupported</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
