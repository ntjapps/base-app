<script setup lang="ts">
import { ref } from "vue";
import axios from "axios";
import { useResponse, useError } from "../AppAxiosResp";
import { useApiStore } from "../AppState";

import { timeGreetings } from "../AppCommon";

import CmpLayout from "../Components/CmpLayout.vue";
import InputText from "primevue/inputtext";
import ButtonVue from "primevue/button";
import Password from "primevue/password";

const api = useApiStore();

const props = defineProps({
    appName: {
        type: String,
        required: true,
    },
    greetings: {
        type: String,
        required: true,
    },
    userName: {
        type: String,
        required: true,
    },
});

const name = ref<string | null>(props.userName);
const newPassword = ref<string | null>("");
const confirmPassword = ref<string | null>("");

const timeGreet = timeGreetings();

const postProfileData = () => {
    axios
        .post(api.postProfile, {
            name: name.value,
            password: newPassword.value,
            password_confirmation: confirmPassword.value,
        })
        .then((response) => {
            useResponse(response);
        })
        .catch((error) => {
            useError(error);
        });
};
</script>

<template>
    <CmpLayout>
        <div class="my-3 mx-5 p-5 bg-white rounded-lg drop-shadow-lg">
            <h2 class="title-font font-bold">{{ timeGreet + greetings }}</h2>
            <h3 class="title-font">Update profile in {{ appName }}</h3>
            <div class="mt-10 mb-5">
                <span class="p-float-label w-full">
                    <InputText
                        id="name"
                        v-model="name"
                        type="text"
                        class="w-full"
                        @keyup.enter="postProfileData"
                    />
                    <label class="w-full" for="name">Name</label>
                </span>
            </div>
            <div class="mt-10 mb-5">
                <span class="p-float-label w-full">
                    <Password
                        id="newpassword"
                        v-model="newPassword"
                        class="w-full"
                        input-class="w-full"
                        :toggle-mask="true"
                        @keyup.enter="postProfileData"
                    />
                    <label class="w-full" for="newpas"
                        >New Password (Must be filled if changing password,
                        leave empty if don't want to change password)</label
                    >
                </span>
            </div>
            <div class="mt-10 mb-5">
                <span class="p-float-label w-full">
                    <Password
                        id="confirmpassword"
                        v-model="confirmPassword"
                        class="w-full"
                        input-class="w-full"
                        :toggle-mask="true"
                        :feedback="false"
                        @keyup.enter="postProfileData"
                    />
                    <label class="w-full" for="confi"
                        >Confirm Password (Must be filled if changing password,
                        leave empty if don't want to change password)</label
                    >
                </span>
            </div>
            <div class="flex justify-center">
                <ButtonVue
                    class="p-button-primary p-button-sm"
                    label="Update Profile"
                    @click="postProfileData"
                />
            </div>
        </div>
    </CmpLayout>
</template>
