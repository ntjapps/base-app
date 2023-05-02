<script setup lang="ts">
import axios from "axios";
import { ref } from "vue";
import { storeToRefs } from "pinia";
import { useMainStore, useWebApiStore } from "../AppState";

import CmpTurnstile from "../Components/CmpTurnstile.vue";
import CmpToast from "../Components/CmpToast.vue";

import InputText from "primevue/inputtext";
import Password from "primevue/password";
import ProgressSpinner from "primevue/progressspinner";

const webapi = useWebApiStore();
const main = useMainStore();
const { appName, turnstileToken, browserSuppport } = storeToRefs(main);

const username = ref("");
const password = ref("");
const loading = ref(false);
const turnchild = ref<typeof CmpTurnstile>();
const toastchild = ref<typeof CmpToast>();

const postLogindata = () => {
    loading.value = true;
    axios
        .post(webapi.postLogin, {
            username: username.value,
            password: password.value,
            token: turnstileToken,
        })
        .then((response) => {
            clearData();
            toastchild.value?.toastSuccess("Welcome to " + appName);
            window.location.href = response.data.redirect;
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
        class="grid content-center w-screen h-screen bg-slate-200 object-fill bg-no-repeat bg-cover bg-center"
    >
        <div class="flex justify-center">
            <div v-show="!loading" class="bg-white rounded-lg drop-shadow-lg">
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
                    <div
                        class="flex justify-center flex-col mt-8 my-2.5 p-float-label"
                    >
                        <div class="w-full">
                            <span class="p-float-label w-full">
                                <InputText
                                    id="username"
                                    v-model="username"
                                    type="text"
                                    class="text-center w-full"
                                    @keyup.enter="postLogindata"
                                />
                                <label class="w-full" for="username"
                                    >Username</label
                                >
                            </span>
                        </div>
                    </div>
                    <div
                        class="flex justify-center flex-col mt-8 my-2.5 p-float-label"
                    >
                        <div class="w-full">
                            <span class="p-float-label w-full">
                                <Password
                                    id="password"
                                    v-model="password"
                                    type="text"
                                    class="w-full"
                                    input-class="w-full text-center"
                                    :feedback="false"
                                    @keyup.enter="postLogindata"
                                />
                                <label class="w-full" for="password"
                                    >Password</label
                                >
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
            <div v-show="loading" class="bg-white rounded-lg drop-shadow-lg">
                <div class="m-auto p-5">
                    <div class="text-center font-bold my-2.5">
                        <ProgressSpinner />
                    </div>
                    <div class="text-center font-bold my-2.5">Loading</div>
                </div>
            </div>
        </div>
    </div>
</template>
