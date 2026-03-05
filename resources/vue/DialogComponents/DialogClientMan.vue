<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { api } from '../AppAxios';
import useClipboard from 'vue-clipboard3';
import { ClientListDataInterface } from '../AppCommon';
import StdButton from '../Components/StdButton.vue';

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
        const response = await api.postDeleteOauthClient(id);

        // Handle 202 Accepted or success
        api.handle202Accepted(response, 'OAuth client deletion task queued for processing');
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

        // Extract secret from response
        const responseData = response.data.data as {
            secret: string;
            task_id?: string;
            message?: string;
        };
        newClientSecret.value = responseData.secret;

        // Handle 202 Accepted or success
        api.handle202Accepted(response, 'OAuth client secret reset task queued for processing');
    } catch {
        // Toast handled globally by ApiClient
    }
};

const updateClient = async (id: string) => {
    try {
        const response = await api.postUpdateOauthClient({
            id: id,
            name: clientName.value,
            redirect: clientRedirect.value,
        });

        // Handle 202 Accepted or success
        api.handle202Accepted(response, 'OAuth client update task queued for processing');
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
    <div class="space-y-4">
        <div v-if="showClientId" class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>ID:</span>
            </div>
            <div class="w-full text-sm">
                <UInput
                    v-model="computedClientId"
                    class="w-full text-sm"
                    @click="copyIdToClipboard"
                />
            </div>
        </div>

        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Name:<span class="text-red-500 font-bold">*</span></span>
            </div>
            <div class="w-full text-sm">
                <UInput v-model="clientName" class="w-full text-sm" :disabled="!allowEditName" />
            </div>
        </div>

        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Redirect:</span>
            </div>
            <div class="w-full text-sm">
                <UInput
                    v-model="clientRedirect"
                    class="w-full text-sm"
                    :disabled="!allowEditName"
                />
            </div>
        </div>

        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Old Client Secret:</span>
            </div>
            <div class="w-full text-sm">
                <UInput
                    v-model="oldClientSecret"
                    class="w-full text-sm"
                    :disabled="!allowEditName"
                />
            </div>
        </div>

        <div v-if="showClientSecret" class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>New Secret:</span>
            </div>
            <div class="w-full text-sm">
                <UInput
                    v-model="computedClientSecret"
                    class="w-full text-sm"
                    @click="copySecretToClipboard"
                />
            </div>
        </div>

        <div class="flex w-full flex-wrap justify-end gap-2 border-t border-gray-200 pt-3">
            <StdButton variant="danger" label="Delete" @click="deleteClient(clientId)" />
            <StdButton variant="warn" label="Reset Secret" @click="resetClient(clientId)" />
            <StdButton
                v-if="!typeCreate"
                variant="success"
                label="Update Client"
                @click="updateClient(clientId)"
            />
            <StdButton
                v-if="showCreateClient"
                variant="success"
                label="Create Client"
                @click="createClient"
            />
            <StdButton
                v-if="!showCreateClient && typeCreate"
                variant="success"
                label="Close & Refresh"
                @click="closeDialogFunction"
            />
        </div>
    </div>
</template>
