<script setup lang="ts">
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useWebStore } from '../AppRouter';
import { useApiStore } from '../AppState';

import CmpToast from '../Components/CmpToast.vue';

const web = useWebStore();
const api = useApiStore();
const router = useRouter();
const toastchild = ref<typeof CmpToast>();

onMounted(() => {
    axios
        .post(api.postTokenLogout)
        .then(() => {
            router.push(web.landingPage);
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: 'error',
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
});
</script>

<template>
    <CmpToast ref="toastchild" />
</template>
