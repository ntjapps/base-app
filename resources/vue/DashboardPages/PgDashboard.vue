<script setup lang="ts">
import { onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { timeGreetings } from '../AppCommon';
import { useMainStore } from '../AppState';

import CmpLayout from '../Components/CmpLayout.vue';

const props = defineProps<{
    appName: string;
    greetings: string;
    expandedKeysProps: string;
}>();
const timeGreet = timeGreetings();
const main = useMainStore();
const { userName } = storeToRefs(main);

onMounted(() => {
    main.updateExpandedKeysMenu(props.expandedKeysProps);
});
</script>

<template>
    <CmpLayout>
        <div
            class="my-2 md:my-3 mx-2 md:mx-5 p-3 md:p-5 bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg w-full max-w-xl"
        >
            <h2 class="title-font font-bold">
                {{ timeGreet + userName }}
            </h2>
            <h3 class="title-font">Welcome to {{ appName }}</h3>
        </div>
    </CmpLayout>
</template>
