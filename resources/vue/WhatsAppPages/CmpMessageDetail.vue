<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch, computed, nextTick } from 'vue';
import { useEchoStore } from '../AppState';
import { storeToRefs } from 'pinia';
import { useMainStore } from '../AppState';
import { api } from '../AppAxios';
import CmpToast from '../Components/CmpToast.vue';
import StdButton from '../Components/StdButton.vue';

interface User {
    id: string;
    name: string;
    email: string;
}

interface Messageable {
    id: string;
    message_content?: string;
    message_body?: string;
    contact_name?: string;
    message_type?: string;
    message_id: string;
    recipient_number?: string;
    contact_wa_id?: string;
    sent_by_user?: User;
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

const main = useMainStore();
const toastchild = ref<InstanceType<typeof CmpToast> | null>(null);
const messageDetail = ref<MessageDetail[]>([]);
const replyMessage = ref('');
const isSubmitting = ref(false);
const messagesContainer = ref<HTMLElement | null>(null);
const echo = useEchoStore();
const { laravelEcho } = storeToRefs(echo);
const isNearBottom = ref<boolean>(true);
const canReply = computed(() => {
    return (
        main.permissions?.includes('whatsapp.reply') ||
        main.permissions?.includes('super.admin') ||
        main.permissions?.includes('*')
    );
});

const sortedMessages = computed(() => {
    return [...messageDetail.value].sort(
        (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime(),
    );
});

const lastUserMessage = computed(() => {
    return sortedMessages.value
        .slice()
        .reverse()
        .find((m) => m.messageable_type.includes('WaMessageWebhookLog'));
});

const isSessionExpired = computed(() => {
    if (!lastUserMessage.value) return true;
    const messageTime = new Date(lastUserMessage.value.created_at).getTime();
    const now = Date.now();
    const twentyFourHours = 24 * 60 * 60 * 1000;
    // Reduce window by 1 minute to match backend safety margin
    const safetyMargin = 1 * 60 * 1000;
    return now - messageTime > twentyFourHours - safetyMargin;
});

type BroadcastWebhook = {
    id: string;
    body?: string;
    message_id?: string;
    from?: string;
    raw_data?: Messageable['raw_data'] | null;
    created_at?: string;
    timestamp?: string;
};

type BroadcastSent = {
    id: string;
    message?: string;
    recipient_number?: string;
    sent_by_user?: User | null;
    created_at?: string;
};

type BroadcastThread = {
    phone_number?: string;
};

type BroadcastPayload = {
    webhook_log?: BroadcastWebhook;
    sent_log?: BroadcastSent;
    thread?: BroadcastThread;
};

const handleMessageReceived = (payload?: BroadcastPayload) => {
    // If no payload was delivered (legacy behaviour or test), fall back to a full fetch
    if (!payload) {
        // only update detail silently and only scroll if user is near bottom
        getMessageDetails({ silent: true, shouldScroll: isNearBottom.value });
        return;
    }

    // Otherwise, process the payload directly to update UI without an API trip
    processPayload(payload);
};

const processPayload = (payload?: BroadcastPayload) => {
    try {
        const webhook = payload?.webhook_log;
        const sent = payload?.sent_log;
        const thread = payload?.thread;

        const currentPhone = props.dialogData?.phone_number;
        if (!currentPhone) return;

        // Determine phone number for the event
        const phone = thread?.phone_number || webhook?.from || sent?.recipient_number;
        if (!phone || phone !== currentPhone) return; // not for this dialog

        // Ensure we append either webhook (inbound) or sent (outbound)
        let newEntry: MessageDetail | null = null;
        const ts =
            webhook?.created_at ||
            webhook?.timestamp ||
            sent?.created_at ||
            new Date().toISOString();

        if (webhook) {
            newEntry = {
                id: String(webhook.id),
                phone_number: currentPhone,
                messageable_type: 'App\\Models\\WaApiMeta\\WaMessageWebhookLog',
                messageable_id: String(webhook.id),
                last_message_at: ts,
                created_at: ts,
                updated_at: ts,
                messageable: {
                    id: String(webhook.id),
                    message_body: webhook.body,
                    message_id: webhook.message_id,
                    contact_wa_id: webhook.from,
                    raw_data: webhook.raw_data ?? undefined,
                    created_at: ts,
                } as Messageable,
            } as MessageDetail;
        } else if (sent) {
            newEntry = {
                id: String(sent.id),
                phone_number: currentPhone,
                messageable_type: 'App\\Models\\WaApiMeta\\WaMessageSentLog',
                messageable_id: String(sent.id),
                last_message_at: ts,
                created_at: ts,
                updated_at: ts,
                messageable: {
                    id: String(sent.id),
                    message_content: sent.message,
                    sent_by_user: sent.sent_by_user ?? undefined,
                    created_at: ts,
                } as Messageable,
            } as MessageDetail;
        }

        if (newEntry) {
            // Prevent duplicates by messageable_id
            const exists = messageDetail.value.find(
                (m) => m.messageable_id === newEntry!.messageable_id,
            );
            if (!exists) {
                messageDetail.value.push(newEntry as MessageDetail);
                nextTick(() => {
                    if (isNearBottom.value) scrollToBottom();
                });
            }
        }
    } catch (err) {
        console.debug('Failed to process broadcast payload', err);
        // Last resort: refresh
        getMessageDetails({ silent: true, shouldScroll: isNearBottom.value });
    }
};

const subscribeToEcho = () => {
    try {
        const echoInstance = laravelEcho.value as unknown as
            | { private?: (ch: string) => { listen?: (event: string, cb: () => void) => void } }
            | undefined;
        if (echoInstance && typeof echoInstance.private === 'function') {
            const channel = echoInstance.private('whatsapp.messages');
            if (channel && typeof channel.listen === 'function') {
                channel.listen('WhatsappMessageReceived', handleMessageReceived);
                channel.listen('WhatsappMessageSent', handleMessageReceived);
            }
        }
    } catch (err) {
        console.debug('Echo private channel not available.', err);
    }
};

const unsubscribeFromEcho = () => {
    try {
        const echoInstance = laravelEcho.value as unknown as
            | {
                  private?: (ch: string) => {
                      stopListening?: (event: string, cb: () => void) => void;
                  };
              }
            | undefined;
        if (echoInstance && typeof echoInstance.private === 'function') {
            const channel = echoInstance.private('whatsapp.messages');
            if (channel && typeof channel.stopListening === 'function') {
                channel.stopListening('WhatsappMessageReceived', handleMessageReceived);
                channel.stopListening('WhatsappMessageSent', handleMessageReceived);
            }
        }
    } catch (err) {
        console.debug('Echo stopListening failed.', err);
    }
};

watch(
    () => props.dialogOpen,
    (newValue) => {
        if (newValue) {
            getMessageDetails();
            subscribeToEcho();
        } else {
            unsubscribeFromEcho();
            emit('closeDialog');
        }
        emit('update:dialogOpen', newValue);
    },
);

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const updateIsNearBottom = () => {
    if (!messagesContainer.value) return;
    const container = messagesContainer.value;
    const buffer = 100; // px before bottom to be considered 'near bottom'
    isNearBottom.value =
        container.scrollTop + container.clientHeight >= container.scrollHeight - buffer;
};

const closeDialogFunction = () => {
    emit('closeDialog');
    emit('update:dialogOpen', false);
};

const getMessageDetails = async (options?: { silent?: boolean; shouldScroll?: boolean }) => {
    const opts = { silent: false, shouldScroll: true, ...(options ?? {}) };
    try {
        const response = await api.getWhatsappMessagesDetail({
            phone_number: props.dialogData?.phone_number,
        });
        messageDetail.value = response.data as unknown as MessageDetail[];
        nextTick(() => {
            if (opts.shouldScroll) {
                scrollToBottom();
            }
        });
    } catch (error) {
        if (!opts.silent) {
            toastchild.value?.toastDisplay(error);
        }
    }
};

const sendReply = async () => {
    if (!replyMessage.value.trim() || isSubmitting.value) return;

    isSubmitting.value = true;
    try {
        const response = await api.postReplyWhatsappMessage({
            phone_number: props.dialogData?.phone_number,
            message: replyMessage.value,
        });
        toastchild.value?.toastDisplay({
            severity: 'success',
            summary: 'Success',
            detail: response.data.message,
        });
        replyMessage.value = '';
        await getMessageDetails(); // Refresh the thread after successful reply
    } catch (error) {
        toastchild.value?.toastDisplay(error);
    } finally {
        isSubmitting.value = false;
    }
};

onMounted(() => {
    if (props.dialogOpen) {
        getMessageDetails();
        subscribeToEcho();
    }
    // Attach scroll listener to detect if we are at bottom
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.addEventListener('scroll', updateIsNearBottom);
        }
    });
});

onUnmounted(() => {
    unsubscribeFromEcho();
    if (messagesContainer.value) {
        messagesContainer.value.removeEventListener('scroll', updateIsNearBottom);
    }
});
</script>

<template>
    <div class="space-y-4">
        <CmpToast ref="toastchild" />
        <div class="flex h-[80vh] flex-col">
            <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4">
                <div class="text-lg font-semibold text-gray-900">Thread Information</div>
                <div class="space-y-1 text-sm text-gray-700">
                    <div>
                        <span class="font-medium">Phone:</span> {{ dialogData?.phone_number }}
                    </div>
                    <div v-if="messageDetail[0]?.messageable?.contact_name">
                        <span class="font-medium">Contact Name:</span>
                        {{ messageDetail[0]?.messageable?.contact_name }}
                    </div>
                </div>
            </div>

            <div
                ref="messagesContainer"
                class="mb-4 flex-1 space-y-4 overflow-y-auto rounded-xl border border-gray-200 bg-gray-50 p-4"
            >
                <div v-if="messageDetail.length === 0" class="text-center text-gray-500 py-8">
                    No messages found.
                </div>
                <div v-for="message in sortedMessages" :key="message.id" class="flex flex-col">
                    <template v-if="message.messageable_type.includes('WaMessageSentLog')">
                        <!-- Outgoing Message (Right) -->
                        <div class="flex mb-1">
                            <div class="max-w-[80%] ml-auto">
                                <div class="mb-1 text-right text-xs font-bold text-primary-600">
                                    {{ message.messageable?.sent_by_user?.name || 'System/AI' }}
                                </div>
                                <div
                                    class="rounded-lg rounded-tr-none bg-primary-100 p-3 text-right text-sm whitespace-pre-wrap break-words"
                                >
                                    {{
                                        message.messageable?.message_content ||
                                        message.messageable?.message_body ||
                                        '-'
                                    }}
                                </div>
                                <div class="text-xs text-gray-500 text-right mt-1">
                                    {{ new Date(message.created_at).toLocaleString() }}
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <!-- Incoming Message (Left) -->
                        <div class="flex justify-start mb-1">
                            <div class="max-w-[80%]">
                                <div class="mb-1 text-left text-xs font-bold text-gray-600">
                                    {{ message.messageable?.contact_name || 'User' }}
                                </div>
                                <div
                                    class="rounded-lg rounded-tl-none border bg-white p-3 text-sm whitespace-pre-wrap break-words shadow-sm"
                                >
                                    {{
                                        message.messageable?.message_content ||
                                        message.messageable?.message_body ||
                                        '-'
                                    }}
                                </div>
                                <div class="text-xs text-gray-500 text-left mt-1">
                                    {{ new Date(message.created_at).toLocaleString() }}
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex-none rounded-xl border border-gray-200 bg-white p-4">
                <div
                    v-if="isSessionExpired"
                    class="mb-4 rounded border border-yellow-200 bg-yellow-50 p-3 text-center text-sm text-yellow-800"
                >
                    Session expired. You can only reply within 24 hours of the last user message.
                </div>

                <div class="space-y-4">
                    <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
                        <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                            Reply
                        </div>
                        <div class="w-full">
                            <UTextarea
                                v-model="replyMessage"
                                :rows="3"
                                placeholder="Type your reply here..."
                                :disabled="isSubmitting || !canReply || isSessionExpired"
                            />
                        </div>
                    </div>

                    <div
                        class="flex w-full flex-wrap justify-end gap-2 border-t border-gray-200 pt-3"
                    >
                        <StdButton
                            variant="primary"
                            label="Send Reply"
                            class="rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700"
                            :loading="isSubmitting"
                            :disabled="!canReply || !replyMessage.trim() || isSessionExpired"
                            @click="sendReply"
                        />
                        <StdButton
                            variant="neutral"
                            label="Close"
                            :disabled="isSubmitting"
                            @click="closeDialogFunction"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
