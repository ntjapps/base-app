<script setup lang="ts">
import axios from "axios";
import { ref } from "vue";
import { storeToRefs } from "pinia";
import { useMainStore, useWebApiStore } from "../AppState";
import { useWebStore } from "../AppRouter";

import CmpTurnstile from "../Components/CmpTurnstile.vue";
import CmpToast from "../Components/CmpToast.vue";

import InputText from "primevue/inputtext";
import Password from "primevue/password";

const web = useWebStore();
const webapi = useWebApiStore();
const main = useMainStore();
const { appName, turnstileToken, browserSuppport } = storeToRefs(main);

const username = ref("");
const password = ref("");
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
            toastchild.value?.toastSuccess(response.data.message);
        })
        .then(() => {
            window.location.href = web.dashboard;
        })
        .catch((error) => {
            loading.value = false;
            toastchild.value?.toastError(error);
        });
    turnchild.value?.resetTurnstile();
};

const clearData = () => {
    username.value = "";
    password.value = "";
};
</script>

<template>
    <CmpToast ref="toastchild" />
    <div
        class="grid content-center w-screen h-screen bg-neutral object-fill bg-no-repeat bg-cover bg-center"
    >
        <div class="flex justify-center">
            <div
                v-show="!loading"
                class="bg-base-300 rounded-lg drop-shadow-lg"
            >
                <div class="m-auto p-5">
                    <div class="text-center font-bold my-2.5">
                        {{ appName }}
                    </div>
                    <div
                        v-if="!browserSuppport"
                        class="text-center font-bold my-2.5"
                    >
                        <button class="btn btn-sm btn-error">
                            <i class="pi pi-times m-1" />
                            <span class="m-1">Browser Unsupported</span>
                        </button>
                    </div>
                    <div class="text-center font-bold my-2.5">
                        Login to your account
                    </div>
                    <div class="flex justify-center flex-col mt-8 my-2.5">
                        <div class="relative w-full">
                            <span class="flex flex-col w-full">
                                <label for="username"> Username </label>
                                <InputText
                                    id="username"
                                    v-model="username"
                                    class="text-left"
                                    placeholder=""
                                    @keypress.enter="postLoginData"
                                />
                            </span>
                        </div>
                    </div>
                    <div class="flex justify-center flex-col my-2.5">
                        <div class="relative w-full">
                            <span class="flex flex-col w-full">
                                <label for="password"> Password </label>
                                <Password
                                    v-model="password"
                                    input-id="password"
                                    type="text"
                                    placeholder=""
                                    :pt="{
                                        input: {
                                            class: 'text-left w-full',
                                        },
                                    }"
                                    :feedback="false"
                                    @keyup.enter="postLoginData"
                                />
                            </span>
                        </div>
                    </div>
                    <div class="flex justify-center py-2.5">
                        <CmpTurnstile ref="turnchild" />
                    </div>
                    <div class="flex justify-center py-2.5">
                        <button class="btn btn-primary" @click="postLogindata">
                            <span class="m-1">Login</span>
                        </button>
                    </div>
                </div>
            </div>
            <div v-show="loading" class="bg-base-200 rounded-lg drop-shadow-lg">
                <div class="m-auto p-5">
                    <div class="text-center font-bold my-2.5">
                        <i
                            class="pi pi-spin pi-spinner"
                            style="font-size: 3rem"
                        ></i>
                    </div>
                    <div class="text-center font-bold my-2.5">Loading</div>
                </div>
            </div>
        </div>
    </div>
</template>
