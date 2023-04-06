<script setup lang="ts">
import axios from "axios";
import { ref, onBeforeMount } from "vue";
import { useRouter } from "vue-router";
import { useWebStore } from "../AppRouter";

import CmpToast from "../Components/CmpToast.vue";

const web = useWebStore();
const router = useRouter();
const toastchild = ref<typeof CmpToast>();

onBeforeMount(() => {
    axios
        .post(import.meta.env.VITE_API_ENDPOINT + "/api/post-token-revoke")
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
