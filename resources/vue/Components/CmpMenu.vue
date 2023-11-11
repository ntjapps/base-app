<script setup lang="ts">
import { storeToRefs } from "pinia";

import { useMainStore } from "../AppState";

import PanelMenu from 'primevue/panelmenu';

const main = useMainStore();
const { appName, menuItems } = storeToRefs(main);
</script>

<template>
    <div class="flex m-5 w-full">
        <div class="flex flex-col w-full">
            <div class="flex flex-row">
                <div class="flex justify-center w-1/5 mx-1">
                    Profile Image
                </div>
                <div class="flex font-bold text-center mx-1">
                    {{ appName }}
                </div>
            </div>
            <div class="flex w-full mt-10">
                <PanelMenu :model="menuItems" />
            </div>
        </div>
    </div>
</template>