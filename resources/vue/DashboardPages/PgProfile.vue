<script setup lang="ts">
import { ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useMainStore } from '../AppState';
import { api } from '../AppAxios';
import CmpLayout from '../Components/CmpLayout.vue';
import CmpToast from '../Components/CmpToast.vue';
import StdButton from '../Components/StdButton.vue';

const main = useMainStore();
const { userName, appName } = storeToRefs(main);

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
</script>

<template>
    <CmpLayout>
        <CmpToast ref="toastchild" />
        <div class="mx-auto w-full max-w-6xl space-y-5">
            <div
                class="rounded-xl border border-gray-200 bg-gradient-to-r from-white to-gray-50 p-6 shadow-sm"
            >
                <h2 class="text-4xl font-semibold tracking-tight text-gray-800">Profile</h2>
                <h3 class="mt-1 text-sm text-gray-600">Update profile in {{ appName }}</h3>
            </div>

            <div class="w-full max-w-3xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700"
                        >Name</label
                    >
                    <UInput
                        id="name"
                        v-model="userName"
                        type="text"
                        class="w-full"
                        @keyup.enter="postProfileData"
                    />
                </div>

                <div class="mb-5">
                    <label for="newpas" class="mb-1 block w-full text-sm font-medium text-gray-700"
                        >New Password</label
                    >
                    <UInput
                        id="newpassword"
                        v-model="newPassword"
                        type="password"
                        class="w-full"
                        @keyup.enter="postProfileData"
                    />
                    <div class="mt-1 text-sm text-gray-500">
                        Must be filled if changing password, leave empty if don't want to change
                        password.
                    </div>
                </div>

                <div class="mb-6">
                    <label for="confi" class="mb-1 block w-full text-sm font-medium text-gray-700"
                        >Confirm Password</label
                    >
                    <UInput
                        id="confirmpassword"
                        v-model="confirmPassword"
                        type="password"
                        class="w-full"
                        @keyup.enter="postProfileData"
                    />
                    <div class="mt-1 text-sm text-gray-500">
                        Must be filled if changing password, leave empty if don't want to change
                        password.
                    </div>
                </div>

                <div class="flex justify-end">
                    <StdButton
                        variant="primary"
                        label="Update Profile"
                        class="rounded-md bg-green-600 px-5 py-2.5 text-white hover:bg-green-700"
                        @click="postProfileData"
                    />
                </div>
            </div>
        </div>
    </CmpLayout>
</template>
