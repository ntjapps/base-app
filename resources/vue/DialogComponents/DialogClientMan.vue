<script setup lang="ts">
import axios from "axios";
import { ref, inject, computed } from "vue";
import useClipboard from "vue-clipboard3";
import { useApiStore } from "../AppState";

import CmpToast from "../Components/CmpToast.vue";
import InputText from "primevue/inputtext";

const api = useApiStore();
const toastchild = ref<typeof CmpToast>();
const { toClipboard } = useClipboard();

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const dialogRef = inject("dialogRef") as any;

const typeCreate = dialogRef.value.data?.typeCreate;
const clientmanData = dialogRef.value.data?.clientmanData;
const clientId = ref<string>(clientmanData?.id);
const clientName = ref<string>(clientmanData?.name);
const clientRedirect = ref<string>(clientmanData?.redirect);
const newClientSecret = ref<string>("");

const showClientId = computed(() => {
    return clientId.value?.length > 0;
});

const computedClientId = computed(() => {
    return clientId.value;
});

const copyIdToClipboard = () => {
    toClipboard(clientId.value);
    toastchild.value?.toastDisplay({
        severity: "success",
        summary: "Success Copy ID",
    });
};

const showClientSecret = computed(() => {
    return newClientSecret.value?.length > 0;
});

const computedClientSecret = computed(() => {
    return newClientSecret.value;
});

const copySecretToClipboard = () => {
    toClipboard(newClientSecret.value);
    toastchild.value?.toastDisplay({
        severity: "success",
        summary: "Success Copy Secret",
    });
};

const deleteClient = (id: string) => {
    axios
        .post(api.postDeleteOauthClient, {
            id: id,
        })
        .then((response) => {
            toastchild.value?.toastDisplay({
                severity: "success",
                summary: response.data.title,
                detail: response.data.message,
            });
            dialogRef.value.close();
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: "error",
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
};

const resetClient = (id: string) => {
    axios
        .post(api.postResetOauthSecret, { id: id })
        .then((response) => {
            newClientSecret.value = response.data.data.secret;
            toastchild.value?.toastDisplay({
                severity: "success",
                summary: response.data.title,
                detail: response.data.message,
            });
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: "error",
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
};

const updateClient = (id: string) => {
    axios
        .post(api.postUpdateOauthClient, {
            id: id,
            name: clientName.value,
            redirect: clientRedirect.value,
        })
        .then((response) => {
            toastchild.value?.toastDisplay({
                severity: "success",
                summary: response.data.title,
                detail: response.data.message,
            });
            dialogRef.value.close();
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: "error",
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
};

const createClient = () => {
    axios
        .post(api.postCreateOauthClient, {
            name: clientName.value,
            redirect: clientRedirect.value,
        })
        .then((response) => {
            clientId.value = response.data.data.id;
            newClientSecret.value = response.data.data.secret;
            toastchild.value?.toastDisplay({
                severity: "success",
                summary: response.data.title,
                detail: response.data.message,
            });
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: "error",
                summary: error.response.data.title,
                detail: error.response.data.message,
                response: error,
            });
        });
};

const showCreateClient = computed(() => {
    return typeCreate && !showClientId.value;
});

const allowEditName = computed(() => {
    return (!typeCreate && showClientId.value) || showCreateClient.value;
});
</script>

<template>
    <CmpToast ref="toastchild" />
    <div v-if="showClientId" class="flex w-full mt-1">
        <div class="w-28 my-auto text-sm">
            <span>ID:</span>
        </div>
        <div class="flex w-full text-sm">
            <InputText
                v-model="computedClientId"
                v-tooltip.top="'Click to copy'"
                class="w-full text-sm"
                @click="copyIdToClipboard"
            />
        </div>
    </div>
    <div class="flex w-full mt-1">
        <div class="w-28 my-auto text-sm">
            <span>Name:<span class="text-red-500 font-bold">*</span></span>
        </div>
        <div class="flex w-full text-sm">
            <InputText
                v-model="clientName"
                class="w-full text-sm"
                :disabled="!allowEditName"
            />
        </div>
    </div>
    <div class="flex w-full mt-1">
        <div class="w-28 my-auto text-sm">
            <span>Redirect:</span>
        </div>
        <div class="flex w-full text-sm">
            <InputText
                v-model="clientRedirect"
                class="w-full text-sm"
                :disabled="!allowEditName"
            />
        </div>
    </div>
    <div v-if="showClientSecret" class="flex w-full mt-1">
        <div class="w-28 my-auto text-sm">
            <span>New Secret:</span>
        </div>
        <div class="flex w-full text-sm">
            <InputText
                v-model="computedClientSecret"
                v-tooltip.top="'Click to copy'"
                class="w-full text-sm"
                @click="copySecretToClipboard"
            />
        </div>
    </div>
    <div class="flex w-full mt-2.5 justify-center">
        <button class="btn btn-error mx-2" @click="deleteClient(clientId)">
            <span class="m-1">Delete</span>
        </button>
        <button class="btn btn-warning mx-2" @click="resetClient(clientId)">
            <span class="m-1">Reset Secret</span>
        </button>
        <button
            v-if="!typeCreate"
            class="btn btn-success mx-2"
            @click="updateClient(clientId)"
        >
            <span class="m-1">Update Client</span>
        </button>
        <button
            v-if="showCreateClient"
            class="btn btn-success mx-2"
            @click="createClient"
        >
            <span class="m-1">Create Client</span>
        </button>
    </div>
</template>
