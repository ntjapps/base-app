<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useApiStore, useMainStore } from '../AppState';

import CmpLayout from '../Components/CmpLayout.vue';
import CmpToast from '../Components/CmpToast.vue';

import InputText from '../volt/InputText.vue';
import Password from '../volt/Password.vue';
import Message from '../volt/Message.vue';

const props = defineProps<{
    appName: string;
    greetings: string;
    expandedKeysProps: string;
}>();
const api = useApiStore();
const main = useMainStore();
const { userName } = storeToRefs(main);

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

onMounted(() => {
    main.updateExpandedKeysMenu(props.expandedKeysProps);
});
</script>

<template>
    <CmpLayout>
        <CmpToast ref="toastchild" />
        <div
            class="my-2 md:my-3 mx-2 md:mx-5 p-3 md:p-5 bg-surface-200 rounded-lg drop-shadow-lg w-full max-w-xl"
        >
            <h3 class="title-font">Update profile in {{ appName }}</h3>
            <div class="mt-6 md:mt-10 mb-3 md:mb-5">
                <label for="name">Name</label>
                <InputText
                    id="name"
                    v-model="userName"
                    type="text"
                    class="w-full"
                    @keyup.enter="postProfileData"
                />
            </div>
            <div class="mt-6 md:mt-10 mb-3 md:mb-5">
                <label class="w-full" for="newpas">New Password</label>
                <Password
                    id="newpassword"
                    v-model="newPassword"
                    class="w-full"
                    inputClass="w-full"
                    toggleMask
                    @keyup.enter="postProfileData"
                />
                <Message
                    >Must be filled if changing password, leave empty if don't want to change
                    password</Message
                >
            </div>
            <div class="mt-6 md:mt-10 mb-3 md:mb-5">
                <label class="w-full" for="confi"
                    >Confirm Password (Must be filled if changing password, leave empty if don't
                    want to change password)</label
                >
                <Password
                    id="confirmpassword"
                    v-model="confirmPassword"
                    class="w-full"
                    inputClass="w-full"
                    toggleMask
                    :feedback="false"
                    @keyup.enter="postProfileData"
                />
                <Message
                    >Must be filled if changing password, leave empty if don't want to change
                    password</Message
                >
            </div>
            <div class="flex justify-center">
                <UButton size="xl" label="Update Profile" @click="postProfileData" />
            </div>
        </div>
    </CmpLayout>
</template>
