<script setup lang="ts">
import { ref, onBeforeMount } from "vue";
import { timeGreetings } from "../AppCommon";

import CmpLayout from "../Components/CmpLayout.vue";

const timeGreet = timeGreetings();

const props = defineProps({
    appName: {
        type: String,
        required: true,
    },
    greetings: {
        type: String,
        required: true,
    },
});

const clock = ref<string | null>(new Date().toLocaleString("en-UK"));

onBeforeMount(() => {
    /** Ticking clock */
    setInterval(() => {
        clock.value = new Date().toLocaleString("en-UK");
    });
});
</script>

<template>
    <CmpLayout>
        <div class="my-3 mx-5 p-5 bg-white rounded-lg drop-shadow-lg">
            <h2 class="title-font font-bold">
                {{ timeGreet + props.greetings }}
            </h2>
            <h3 class="title-font">Welcome to {{ props.appName }}</h3>
        </div>
    </CmpLayout>
</template>
