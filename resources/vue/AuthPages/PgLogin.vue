<script setup lang="ts">
import axios from "axios";

import { ref } from "vue";
import { useMainStore, useWebApiStore } from "../AppState";
import { useError } from "../AppAxiosResp";

import CmpTurnstile from "../Components/CmpTurnstile.vue";

import ButtonVue from "primevue/button";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import ProgressSpinner from "primevue/progressspinner";

const webapi = useWebApiStore();
const main = useMainStore();

const props = defineProps({
    appName: {
        type: String,
        required: true,
    },
});

const username = ref("");
const password = ref("");
const loading = ref(false);
const turnchild = ref<typeof CmpTurnstile>();

const postLogindata = () => {
    loading.value = true;
    axios
        .post(webapi.postLogin, {
            username: username.value,
            password: password.value,
            token: main.turnstileToken,
        })
        .then((response) => {
            clearData();
            window.location.href = response.data.redirect;
        })
        .catch((error) => {
            loading.value = false;
            useError(error);
            turnchild.value?.resetTurnstile();
        });
};

const clearData = () => {
    username.value = "";
    password.value = "";
};
</script>

<template>
    <div
        class="grid content-center w-screen h-screen bg-slate-200 object-fill bg-no-repeat bg-cover bg-center"
    >
        <div class="flex justify-center">
            <div v-show="!loading" class="bg-white rounded-lg drop-shadow-lg">
                <div class="m-auto p-5">
                    <div class="text-center font-bold my-2.5">
                        {{ props.appName }}
                    </div>
                    <div
                        v-if="!main.browserSuppport"
                        class="text-center font-bold my-2.5"
                    >
                        <ButtonVue
                            class="p-button-sm p-button-danger"
                            label="Browser Unsupported"
                        />
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
                        <ButtonVue
                            class="p-button-primary p-button-sm"
                            label="Login"
                            @click="postLogindata"
                        />
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
