<script setup lang="ts">
import axios from "axios";
import { ref } from "vue";
import { useApiStore } from "../AppState";

import Button from "primevue/button";

import CmpToast from "./CmpToast.vue";

const api = useApiStore();
const toastchild = ref<typeof CmpToast>();

const postClearCache = () => {
    axios
        .post(api.postClearAppCache)
        .then((response) => {
            toastchild.value?.toastDisplay({
                severity: "success",
                summary: response.data.title,
                detail: response.data.message,
            });
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: "error",
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
};
</script>

<template>
    <div class="flex mx-2">
        <CmpToast ref="toastchild" />
        <Button
            icon="pi pi-sync"
            text
            rounded
            aria-label="Clear Cache"
            @click="postClearCache"
        />
    </div>
</template>
