<script setup lang="ts">
import { storeToRefs } from 'pinia';

import { useMainStore } from '../AppState';

import PanelMenu from 'primevue/panelmenu';
import Drawer from 'primevue/drawer';

const main = useMainStore();
const { menuItems, menuVisible } = storeToRefs(main);
</script>

<template>
    <Drawer v-model:visible="menuVisible" header="Menu">
        <PanelMenu :model="menuItems" />
    </Drawer>
</template>
