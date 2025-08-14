<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useMainStore } from '../AppState';
import { api } from '../AppAxios';
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
const main = useMainStore();
const { userName } = storeToRefs(main);

const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);
const newPassword = ref<string | null>('');
const confirmPassword = ref<string | null>('');

const postProfileData = async () => {
    try {
        await api.postUpdateProfile({
            name: userName.value,
            password: newPassword.value,
            password_confirmation: confirmPassword.value,
        });

        // Clear password fields after successful update
        newPassword.value = '';
        confirmPassword.value = '';
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    }
};

onMounted(() => {
    main.updateExpandedKeysMenu(props.expandedKeysProps);
});
</script>

<template>
    <CmpLayout>
        <CmpToast ref="toastchild" />
        <div
            class="my-2 md:my-3 mx-2 md:mx-5 p-3 md:p-5 bg-surface-200 dark:bg-surface-800 rounded-lg drop-shadow-lg w-full max-w-xl"
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
