<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { api } from '../AppAxios';
import StdButton from '../Components/StdButton.vue';

// Use USwitch from Nuxt UI (registered globally in AppVue)

interface TagDataInterface {
    id: string;
    name: string;
    description: string | null;
    color: string;
    enabled: boolean;
    is_system: boolean;
}

const props = defineProps<{
    dialogOpen: boolean;
    dialogData: TagDataInterface | null;
    dialogTypeCreate: boolean;
}>();
const emit = defineEmits<{
    (e: 'closeDialog'): void;
    (e: 'update:dialogOpen', value: boolean): void;
}>();
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

const tagData = props.dialogData;
const typeCreate = ref<boolean>(props.dialogTypeCreate);
const nameData = ref<string>(tagData?.name ?? '');
const descriptionData = ref<string>(tagData?.description ?? '');
const colorData = ref<string>(tagData?.color ?? '#6366f1');
const enabledData = ref<boolean>(tagData?.enabled ?? true);

const showDeleted = computed(() => {
    return !typeCreate.value;
});

const postTagManData = async () => {
    try {
        await api.postTagManSubmit({
            type_create: typeCreate.value ? 1 : 0,
            id: tagData?.id,
            name: nameData.value,
            description: descriptionData.value,
            color: colorData.value,
            enabled: enabledData.value,
        });
        closeDialogFunction();
    } catch {
        // Toast handled globally by ApiClient
    }
};

const postDeleteTagManData = async () => {
    try {
        const id = tagData?.id as string | number;
        await api.postDeleteTagManSubmit(id);
        closeDialogFunction();
    } catch {
        // Toast handled globally by ApiClient
    }
};
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="tagData?.is_system"
            class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded text-sm text-blue-800 dark:text-blue-200"
        >
            <UIcon name="i-heroicons-information-circle" class="mr-2" />
            This is a system tag and cannot be modified or deleted.
        </div>

        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Name:<span class="text-red-500 font-bold">*</span></span>
            </div>
            <div class="w-full text-sm">
                <UInput v-model="nameData" class="w-full text-sm" :disabled="tagData?.is_system" />
            </div>
        </div>

        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Description:</span>
            </div>
            <div class="w-full text-sm">
                <UTextarea
                    v-model="descriptionData"
                    class="w-full text-sm"
                    :rows="3"
                    :disabled="tagData?.is_system"
                />
            </div>
        </div>

        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Color:<span class="text-red-500 font-bold">*</span></span>
            </div>
            <div class="w-full text-sm">
                <div class="flex items-center gap-2">
                    <input
                        v-model="colorData"
                        type="color"
                        class="w-10 h-10 rounded cursor-pointer border border-surface-300 dark:border-surface-700"
                        :disabled="tagData?.is_system"
                    />
                    <UInput
                        v-model="colorData"
                        class="w-32 text-sm"
                        placeholder="#6366f1"
                        :disabled="tagData?.is_system"
                    />
                </div>
            </div>
        </div>

        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Enabled:</span>
            </div>
            <div class="w-full pt-2 text-sm">
                <USwitch v-model="enabledData" :disabled="tagData?.is_system" />
            </div>
        </div>

        <div class="flex w-full flex-wrap justify-end gap-2 border-t border-gray-200 pt-3">
            <StdButton
                v-if="!tagData?.is_system"
                class="flex-1 sm:flex-none"
                variant="primary"
                @click="postTagManData"
            >
                <span>Submit</span>
            </StdButton>
            <StdButton class="flex-1 sm:flex-none" variant="neutral" @click="closeDialogFunction">
                <span>Cancel</span>
            </StdButton>
            <StdButton
                v-if="showDeleted && !tagData?.is_system"
                class="flex-1 sm:flex-none"
                variant="danger"
                @click="postDeleteTagManData"
            >
                <span>Delete</span>
            </StdButton>
        </div>
    </div>
</template>
