<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref, watch } from 'vue';
import { useApiStore } from '../AppState';
import CmpToast from '../Components/CmpToast.vue';
import Textarea from '../volt/Textarea.vue';

interface Messageable {
    id: string;
    message_content?: string;
    message_body?: string;
    contact_name?: string;
    message_type?: string;
    message_id: string;
    recipient_number?: string;
    contact_wa_id?: string;
    raw_data?: {
        object: string;
        entry: Array<{
            id: string;
            changes: Array<{
                value: {
                    messaging_product: string;
                    metadata: {
                        display_phone_number: string;
                        phone_number_id: string;
                    };
                    contacts?: Array<{
                        profile: {
                            name: string;
                        };
                        wa_id: string;
                    }>;
                    messages?: Array<{
                        from: string;
                        id: string;
                        timestamp: string;
                        text?: {
                            body: string;
                        };
                        type: string;
                    }>;
                };
                field: string;
            }>;
        }>;
    };
    response_data?: {
        messaging_product: string;
        contacts?: Array<{
            input: string;
            wa_id: string;
        }>;
        messages?: Array<{
            id: string;
        }>;
    };
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

interface MessageDetail {
    id: string;
    phone_number: string;
    messageable_type: string;
    messageable_id: string;
    last_message_at: string;
    created_at: string;
    updated_at: string;
    messageable: Messageable;
}

interface MessageSumData {
    id?: string;
    phone_number?: string;
    last_message_at?: string | null;
    message_preview?: string | null;
    [key: string]: unknown;
}

const props = defineProps<{
    dialogOpen: boolean;
    dialogData: MessageSumData | null;
}>();
const emit = defineEmits<{
    (e: 'closeDialog'): void;
    (e: 'update:dialogOpen', value: boolean): void;
}>();

const api = useApiStore();
const toastchild = ref<typeof CmpToast>();

const messageDetail = ref<MessageDetail[]>([]);
const replyMessage = ref('');
const isSubmitting = ref(false);

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

const getMessageDetails = () => {
    axios
        .post(api.getWaThreadDetail, {
            phone_number: props.dialogData?.phone_number,
        })
        .then((response) => {
            messageDetail.value = response.data;
        })
        .catch((error) => {
            toastchild.value?.toastDisplay({
                severity: 'error',
                summary: error.response?.data?.title || 'Error',
                detail: error.response?.data?.message || error.message,
                response: error,
            });
        });
};

const sendReply = () => {
    if (!replyMessage.value.trim() || isSubmitting.value) return;

    isSubmitting.value = true;
    axios
        .post(api.postReplyWhatsappMessage, {
            phone_number: props.dialogData?.phone_number,
            message: replyMessage.value,
        })
        .then((response) => {
            isSubmitting.value = false;
            toastchild.value?.toastDisplay({
                severity: 'success',
                summary: 'Success',
                detail: response.data.message,
            });
            replyMessage.value = '';
            getMessageDetails(); // Refresh the thread after successful reply
        })
        .catch((error) => {
            isSubmitting.value = false;
            toastchild.value?.toastDisplay({
                severity: 'error',
                summary: error.response?.data?.title || 'Error',
                detail: error.response?.data?.message || error.message,
                response: error,
            });
        });
};

onMounted(() => {
    getMessageDetails();
});
</script>

<template>
    <div>
        <CmpToast ref="toastchild" />
        <div class="space-y-4">
            <div class="space-y-2">
                <div class="font-semibold text-lg">Thread Information</div>
                <div class="text-sm space-y-1">
                    <div>
                        <span class="font-medium">Phone:</span> {{ dialogData?.phone_number }}
                    </div>
                    <div v-if="messageDetail[0]?.messageable?.contact_name">
                        <span class="font-medium">Contact Name:</span>
                        {{ messageDetail[0]?.messageable?.contact_name }}
                    </div>
                </div>
            </div>

            <div v-for="message in messageDetail" :key="message.id" class="space-y-2">
                <div class="border-t pt-2">
                    <div class="flex justify-between items-start">
                        <div class="font-medium text-sm">
                            <template v-if="message.messageable_type.includes('WaMessageSentLog')">
                                Sent from Application
                            </template>
                            <template v-else>
                                Received from {{ message.messageable?.contact_name || 'User' }}
                            </template>
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ new Date(message.created_at).toLocaleString() }}
                        </div>
                    </div>
                    <pre
                        class="mt-2 whitespace-pre-wrap break-words text-sm bg-surface-100 dark:bg-surface-900 p-3 rounded"
                        >{{
                            message.messageable?.message_content ||
                            message.messageable?.message_body ||
                            '-'
                        }}</pre
                    >
                </div>
            </div>

            <div class="flex flex-col gap-4 pt-4 border-t">
                <div class="flex flex-col gap-2">
                    <div class="font-medium text-sm">Reply to Thread</div>
                    <Textarea
                        v-model="replyMessage"
                        :rows="3"
                        placeholder="Type your reply here..."
                        :disabled="isSubmitting"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <UButton
                        size="xl"
                        label="Send Reply"
                        :loading="isSubmitting"
                        :disabled="!replyMessage.trim()"
                        @click="sendReply"
                    />
                    <UButton
                        size="xl"
                        label="Close"
                        :disabled="isSubmitting"
                        @click="closeDialogFunction"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
