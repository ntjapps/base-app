<script setup lang="ts">
import axios from 'axios';
import { ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useApiStore, useMainStore } from '../AppState';

import CmpLayout from '../Components/CmpLayout.vue';
import CmpToast from '../Components/CmpToast.vue';

import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';

const api = useApiStore();
const main = useMainStore();
const { appName, userName } = storeToRefs(main);

const toastchild = ref<typeof CmpToast>();

const newPassword = ref<string | null>('');
const confirmPassword = ref<string | null>('');

const postProfileData = () => {
    const redirect = '';
    axios
        .post(api.postProfile, {
            name: userName.value,
            password: newPassword.value,
            password_confirmation: confirmPassword.value,
        })
        .then((response) => {
            toastchild.value?.toastDisplay({
                severity: 'success',
                summary: response.data.title,
                detail: response.data.message,
            });
        })
        .then(() => {
            window.location.href = redirect;
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: 'error',
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
};
</script>

<template>
    <CmpLayout>
        <CmpToast ref="toastchild" />
        <div class="my-3 mx-5 p-5 bg-base-200 rounded-lg drop-shadow-lg">
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
                        inputClass="w-full"
                        toggleMask
                        @keyup.enter="postProfileData"
                    />
                    <label class="w-full" for="newpas"
                        >New Password (Must be filled if changing password, leave empty if don't
                        want to change password)</label
                    >
                </span>
            </div>
            <div class="mt-10 mb-5">
                <span class="p-float-label w-full">
                    <Password
                        id="confirmpassword"
                        v-model="confirmPassword"
                        class="w-full"
                        inputClass="w-full"
                        toggleMask
                        :feedback="false"
                        @keyup.enter="postProfileData"
                    />
                    <label class="w-full" for="confi"
                        >Confirm Password (Must be filled if changing password, leave empty if don't
                        want to change password)</label
                    >
                </span>
            </div>
            <div class="flex justify-center">
                <Button label="Update Profile" @click="postProfileData" />
            </div>
        </div>
    </CmpLayout>
</template>
