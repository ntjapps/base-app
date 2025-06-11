<script setup lang="ts">
import axios from 'axios';

import { ref, computed, watch } from 'vue';
import { useApiStore } from '../AppState';
import useClipboard from 'vue-clipboard3';
import { ClientListDataInterface } from '../AppCommon';

import CmpToast from '../Components/CmpToast.vue';

import InputText from '../volt/InputText.vue';

const props = defineProps<{
    dialogOpen: boolean;
    dialogData: ClientListDataInterface | null;
    dialogTypeCreate: boolean;
}>();
const emit = defineEmits<{
    (e: 'closeDialog'): void;
    (e: 'update:dialogOpen', value: boolean): void;
}>();
const api = useApiStore();
const toastchild = ref<typeof CmpToast>();
const { toClipboard } = useClipboard();

watch(
    () => props.dialogOpen,
    (newValue) => {
        if (!newValue) {
            emit('closeDialog');
        }
        emit('update:dialogOpen', newValue);
    },
);
const closeDialogFunction = () => {
    emit('closeDialog');
    emit('update:dialogOpen', false);
};

const clientmanData = props.dialogData;
const typeCreate = ref<boolean>(props.dialogTypeCreate);
const clientId = ref<string>(clientmanData?.id ?? '');
const clientName = ref<string>(clientmanData?.name ?? '');
const clientRedirect = ref<string>(clientmanData?.redirect ?? '');
const oldClientSecret = ref<string>('');
const newClientSecret = ref<string>('');

const showClientId = computed(() => {
    return clientId.value?.length > 0;
});

const computedClientId = computed(() => {
    return clientId.value;
});

const copyIdToClipboard = () => {
    toClipboard(clientId.value);
    toastchild.value?.toastDisplay({
        severity: 'success',
        summary: 'Success Copy ID',
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
        severity: 'success',
        detail: 'Success Copy Secret',
    });
};

const deleteClient = (id: string) => {
    axios
        .post(api.postDeleteOauthClient, {
            id: id,
        })
        .then((response) => {
            toastchild.value?.toastDisplay({
                severity: 'success',
                summary: response.data.title,
                detail: response.data.message,
            });
        })
        .then(() => {
            closeDialogFunction();
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

const resetClient = (id: string) => {
    axios
        .post(api.postResetOauthSecret, {
            id: id,
            old_secret: oldClientSecret.value,
        })
        .then((response) => {
            newClientSecret.value = response.data.data.secret;
            toastchild.value?.toastDisplay({
                severity: 'success',
                summary: response.data.title,
                detail: response.data.message,
            });
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

const updateClient = (id: string) => {
    axios
        .post(api.postUpdateOauthClient, {
            id: id,
            name: clientName.value,
            redirect: clientRedirect.value,
        })
        .then((response) => {
            toastchild.value?.toastDisplay({
                severity: 'success',
                summary: response.data.title,
                detail: response.data.message,
            });
        })
        .then(() => {
            closeDialogFunction();
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
                severity: 'success',
                summary: response.data.title,
                detail: response.data.message,
            });
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

const showCreateClient = computed(() => {
    return typeCreate.value && !showClientId.value;
});

const allowEditName = computed(() => {
    return (!typeCreate.value && showClientId.value) || showCreateClient.value;
});
</script>

<template>
    <div>
        <CmpToast ref="toastchild" />
        <div v-if="showClientId" class="flex w-full mt-1">
            <div class="w-28 my-auto text-sm">
                <span>ID:</span>
            </div>
            <div class="flex w-full text-sm">
                <InputText
                    v-model="computedClientId"
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
                <InputText v-model="clientName" class="w-full text-sm" :disabled="!allowEditName" />
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
        <div class="flex w-full mt-1">
            <div class="w-28 my-auto text-sm">
                <span>Old Client Secret:</span>
            </div>
            <div class="flex w-full text-sm">
                <InputText
                    v-model="oldClientSecret"
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
                    class="w-full text-sm"
                    @click="copySecretToClipboard"
                />
            </div>
        </div>
        <div class="flex w-full mt-2 justify-center flex-wrap gap-2">
            <UButton
                size="xl"
                class="m-1 md:m-2"
                color="error"
                label="Delete"
                @click="deleteClient(clientId)"
            />
            <UButton
                size="xl"
                class="m-1 md:m-2"
                color="warning"
                label="Reset Secret"
                @click="resetClient(clientId)"
            />
            <UButton
                v-if="!typeCreate"
                size="xl"
                class="m-1 md:m-2"
                color="success"
                label="Update Client"
                @click="updateClient(clientId)"
            />
            <UButton
                v-if="showCreateClient"
                size="xl"
                class="m-1 md:m-2"
                color="success"
                label="Create Client"
                @click="createClient"
            />
            <UButton
                v-if="!showCreateClient && typeCreate"
                size="xl"
                class="m-1 md:m-2"
                color="success"
                label="Close & Refresh"
                @click="closeDialogFunction"
            />
        </div>
    </div>
</template>
