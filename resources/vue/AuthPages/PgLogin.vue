<script setup lang="ts">
import { ref, onBeforeUpdate, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useMainStore } from '../AppState';
import { useWebStore } from '../AppRouter';
import { api } from '../AppAxios';
import StdButton from '../Components/StdButton.vue';
import companyLogo from '../../images/Main Logo.webp';

import CmpTurnstile from '../Components/CmpTurnstile.vue';
import CmpToast from '../Components/CmpToast.vue';

const web = useWebStore();
const main = useMainStore();
const { appName, turnstileToken, browserSuppport, userName } = storeToRefs(main);

const username = ref('');
const password = ref('');
const loading = ref(false);
const turnchild = ref<InstanceType<typeof CmpTurnstile> | null>(null);
const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);

onMounted(() => {
    if (turnchild.value?.resetTurnstile) {
        api.setTurnstileReset(turnchild.value.resetTurnstile);
    }
});

const clearData = () => {
    username.value = '';
    password.value = '';
};

const postLoginData = async () => {
    if (!username.value || !password.value || !turnstileToken.value) return;

    try {
        loading.value = true;
        await api.postLogin({
            username: username.value,
            password: password.value,
            token: turnstileToken.value,
        });
        clearData();
        window.location.href = web.dashboard;
    } finally {
        loading.value = false;
    }
};

onBeforeUpdate(() => {
    if (userName.value !== '') {
        window.location.href = web.dashboard;
    }
});
</script>

<template>
    <div>
        <CmpToast ref="toastchild" />
        <div class="flex min-h-screen w-full items-center justify-center bg-gray-300 p-4">
            <div class="w-full max-w-sm">
                <div
                    v-show="!loading"
                    class="rounded-2xl bg-white px-7 py-6 shadow-[0_16px_36px_rgba(0,0,0,0.16)]"
                >
                    <div class="mb-3 flex justify-center">
                        <img
                            :src="companyLogo"
                            alt="Company logo"
                            class="h-10 w-auto object-contain"
                        />
                    </div>

                    <div class="mb-5 text-center text-xs font-semibold text-gray-700">
                        {{ appName }}
                    </div>

                    <div v-if="!browserSuppport" class="mb-4 text-center">
                        <UButton size="xl" color="red" icon="i-heroicons-x-mark"
                            >Browser Unsupported</UButton
                        >
                    </div>

                    <div class="mb-4 text-center text-2xl font-bold text-gray-900">
                        Login to your account
                    </div>

                    <div class="space-y-2.5">
                        <div class="relative w-full">
                            <UInput
                                id="username"
                                v-model="username"
                                class="w-full text-left"
                                placeholder="Username"
                                @keypress.enter="postLoginData"
                            />
                        </div>

                        <div class="relative w-full">
                            <UInput
                                id="password"
                                v-model="password"
                                type="password"
                                placeholder="Password"
                                class="w-full"
                                @keyup.enter="postLoginData"
                            />
                        </div>

                        <div class="flex justify-center py-1">
                            <CmpTurnstile ref="turnchild" />
                        </div>

                        <div class="pt-1">
                            <StdButton
                                variant="primary"
                                label="Login"
                                class="w-full rounded-md bg-green-600 text-white hover:bg-green-700"
                                @click="postLoginData"
                            />
                        </div>
                    </div>
                </div>
                <div
                    v-show="loading"
                    class="w-full rounded-2xl bg-white px-7 py-10 shadow-[0_16px_36px_rgba(0,0,0,0.16)]"
                >
                    <div class="flex flex-col items-center justify-center gap-4 text-gray-700">
                        <UIcon name="i-heroicons-arrow-path" class="animate-spin text-5xl" />
                        <div class="text-xl font-semibold">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
