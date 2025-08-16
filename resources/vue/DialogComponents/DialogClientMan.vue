<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { api } from '../AppAxios';
import useClipboard from 'vue-clipboard3';
import { ClientListDataInterface } from '../AppCommon';

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
    // optional: rely on OS clipboard notification or add global toast
};

const showClientSecret = computed(() => {
    return newClientSecret.value?.length > 0;
});

const computedClientSecret = computed(() => {
    return newClientSecret.value;
});

const copySecretToClipboard = () => {
    toClipboard(newClientSecret.value);
    // optional: rely on OS clipboard notification or add global toast
};

const deleteClient = async (id: string) => {
    try {
        await api.postDeleteOauthClient(id);
        closeDialogFunction();
    } catch {
        // Toast handled globally by ApiClient
    }
};

const resetClient = async (id: string) => {
    try {
        const response = await api.postResetOauthSecret({
            id: id,
            old_secret: oldClientSecret.value,
        });
        const responseData = response.data.data as { secret: string };
        newClientSecret.value = responseData.secret;
    } catch {
        // Toast handled globally by ApiClient
    }
};

const updateClient = async (id: string) => {
    try {
        await api.postUpdateOauthClient({
            id: id,
            name: clientName.value,
            redirect: clientRedirect.value,
        });
        closeDialogFunction();
    } catch {
        // Toast handled globally by ApiClient
    }
};

const createClient = async () => {
    try {
        const response = await api.postCreateOauthClient({
            name: clientName.value,
            redirect: clientRedirect.value,
        });
        const responseData = response.data.data as { id: string; secret: string };
        clientId.value = responseData.id;
        newClientSecret.value = responseData.secret;
    } catch {
        // Toast handled globally by ApiClient
    }
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
