<script setup lang="ts">
import { storeToRefs } from 'pinia';

import { useMainStore } from '../AppState';

import MenuPanel from '../volt/MenuPanel.vue';
import Drawer from '../volt/DrawerLeft.vue';

const main = useMainStore();
const { menuItems, menuVisible } = storeToRefs(main);
</script>

<template>
    <Drawer v-model:visible="menuVisible" header="Menu">
        <MenuPanel v-model:expandedKeys="expandedKeysMenu" :model="menuItems" />
    </Drawer>
</template>
