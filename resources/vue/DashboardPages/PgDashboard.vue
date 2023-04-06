<script setup lang="ts">
import { ref, onBeforeMount } from "vue";
import { timeGreetings } from "../AppCommon";
import { useMainStore } from "../AppState";

import CmpLayout from "../Components/CmpLayout.vue";

const timeGreet = timeGreetings();
const main = useMainStore();

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
                {{ timeGreet + main.userName }}
            </h2>
            <h3 class="title-font">Welcome to {{ main.appName }}</h3>
        </div>
    </CmpLayout>
</template>
