<script setup lang="ts">
import axios from 'axios';
import { ref, onBeforeUpdate } from 'vue';
import { storeToRefs } from 'pinia';
import { useMainStore, useWebApiStore } from '../AppState';
import { useWebStore } from '../AppRouter';

import CmpTurnstile from '../Components/CmpTurnstile.vue';
import CmpToast from '../Components/CmpToast.vue';

import InputText from '../volt/InputText.vue';
import Password from '../volt/Password.vue';
import LoginSpinner from '../volt/LoginSpinner.vue';

const web = useWebStore();
const webapi = useWebApiStore();
const main = useMainStore();
const { appName, turnstileToken, browserSuppport, userName } = storeToRefs(main);

const username = ref('');
const password = ref('');
const loading = ref(false);
const turnchild = ref<typeof CmpTurnstile>();
const toastchild = ref<typeof CmpToast>();

const postLoginData = () => {
    loading.value = true;
    axios
        .post(webapi.postLogin, {
            username: username.value,
            password: password.value,
            token: turnstileToken.value,
        })
        .then((response) => {
            clearData();
            toastchild.value?.toastDisplay({
                severity: 'success',
                summary: response.data.title,
                detail: response.data.message,
            });
        })
        .then(() => {
            window.location.href = web.dashboard;
        })
        .catch((error) => {
            loading.value = false;
            toastchild.value?.toastDisplay({
                severity: 'error',
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
    turnchild.value?.resetTurnstile();
};

const clearData = () => {
    username.value = '';
    password.value = '';
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
        <div class="flex justify-center w-full min-h-screen bg-surface-100 dark:bg-surface-900">
            <div
                class="flex justify-center w-full max-w-sm sm:max-w-md md:max-w-lg h-fit m-auto px-2 sm:px-0"
            >
                <div
                    v-show="!loading"
                    class="bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg w-full"
                >
                    <div class="m-auto p-4 sm:p-5">
                        <div class="text-center font-bold my-2.5">
                            {{ appName }}
                        </div>
                        <div v-if="!browserSuppport" class="text-center font-bold my-2.5">
                            <UButton size="xl" severity="danger" color="error"
                                ><i class="pi pi-times" />Browser Unsupported</UButton
                            >
                        </div>
                        <div class="text-center font-bold my-2.5">Login to your account</div>
                        <div class="flex justify-center flex-col mt-6 sm:mt-8 my-2.5">
                            <div class="relative w-full">
                                <InputText
                                    id="username"
                                    v-model="username"
                                    class="text-left w-full"
                                    placeholder="Username"
                                    @keypress.enter="postLoginData"
                                />
                            </div>
                        </div>
                        <div class="flex justify-center flex-col my-2.5">
                            <div class="relative w-full">
                                <Password
                                    v-model="password"
                                    inputId="password"
                                    type="text"
                                    placeholder="Password"
                                    :feedback="false"
                                    @keyup.enter="postLoginData"
                                />
                            </div>
                        </div>
                        <div class="flex justify-center py-2.5">
                            <CmpTurnstile ref="turnchild" />
                        </div>
                        <div class="flex justify-center py-2.5">
                            <UButton size="xl" label="Login" @click="postLoginData" />
                        </div>
                    </div>
                </div>
                <div
                    v-show="loading"
                    class="bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg w-full"
                >
                    <div class="m-auto p-4 sm:p-5">
                        <div class="text-center font-bold my-2.5">
                            <LoginSpinner />
                        </div>
                        <div class="text-center font-bold my-2.5">Loading</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
