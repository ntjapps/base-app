<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useWebStore } from '../AppRouter';
import { api } from '../AppAxios';
import CmpToast from '../Components/CmpToast.vue';

const web = useWebStore();
const router = useRouter();
const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);

onMounted(async () => {
    try {
    await api.postTokenRevoke();
        router.push(web.loginPage);
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    }
});
</script>

<template>
    <CmpToast ref="toastchild" />
</template>
