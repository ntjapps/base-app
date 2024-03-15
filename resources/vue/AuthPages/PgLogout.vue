<script setup lang="ts">
import axios from "axios";
import { ref, onBeforeMount } from "vue";
import { useRouter } from "vue-router";
import { useWebStore } from "../AppRouter";
import { useApiStore } from "../AppState";

import CmpToast from "../Components/CmpToast.vue";

const web = useWebStore();
const api = useApiStore();
const router = useRouter();
const toastchild = ref<typeof CmpToast>();

onBeforeMount(() => {
    axios
        .post(api.postTokenLogout)
        .then(() => {
            router.push(web.landingPage);
        })
        .catch((error) => {
            toastchild.value?.toastError(error);
        });
});
</script>

<template>
    <CmpToast ref="toastchild" />
</template>
