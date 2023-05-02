<script setup lang="ts">
import axios from "axios";
import { ref } from "vue";
import { storeToRefs } from "pinia";
import { useApiStore, useMainStore } from "../AppState";

import CmpLayout from "../Components/CmpLayout.vue";
import CmpToast from "../Components/CmpToast.vue";
import InputText from "primevue/inputtext";
import Password from "primevue/password";

const api = useApiStore();
const main = useMainStore();
const { appName, userName } = storeToRefs(main);

const toastchild = ref<typeof CmpToast>();

const newPassword = ref<string | null>("");
const confirmPassword = ref<string | null>("");

const postProfileData = () => {
    axios
        .post(api.postProfile, {
            name: userName.value,
            password: newPassword.value,
            password_confirmation: confirmPassword.value,
        })
        .then((response) => {
            toastchild.value?.toastSuccess(response.data.message);
            window.location.href = response.data.redirect;
        })
        .catch((error) => {
            toastchild.value?.toastError(error);
        });
};
</script>

<template>
    <CmpLayout>
        <CmpToast ref="toastchild" />
        <div class="my-3 mx-5 p-5 bg-white rounded-lg drop-shadow-lg">
            <h3 class="title-font">Update profile in {{ appName }}</h3>
            <div class="mt-10 mb-5">
                <span class="p-float-label w-full">
                    <InputText
                        id="name"
                        v-model="userName"
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
                <button class="btn btn-primary" @click="postProfileData">
                    <span class="m-1">Update Profile</span>
                </button>
            </div>
        </div>
    </CmpLayout>
</template>
