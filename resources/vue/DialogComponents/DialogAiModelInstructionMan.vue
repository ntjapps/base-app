<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { api } from '../AppAxios';
import StdButton from '../Components/StdButton.vue';

interface AiModelInstructionDataInterface {
    id: string;
    name: string;
    key: string;
    instructions: string;
    enabled: boolean;
    scope: Record<string, unknown> | null;
}

const props = defineProps<{
    dialogOpen: boolean;
    dialogData: AiModelInstructionDataInterface | null;
    dialogTypeCreate: boolean;
}>();
const emit = defineEmits<{
    (_e: 'closeDialog'): void;
    (_e: 'update:dialogOpen', value: boolean): void;
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

const instructionData = ref<AiModelInstructionDataInterface | null>(props.dialogData);
const typeCreate = ref<boolean>(props.dialogTypeCreate);
const nameData = ref<string>(instructionData.value?.name ?? '');
const keyData = ref<string>(instructionData.value?.key ?? '');
const instructionsData = ref<string>(instructionData.value?.instructions ?? '');
const enabledData = ref<boolean>(instructionData.value?.enabled ?? true);
const scopeData = ref<string>(
    instructionData.value?.scope ? JSON.stringify(instructionData.value.scope, null, 2) : '',
);

// keep local reactive state in sync with prop changes (when editing different rows without remount)
watch(
    () => props.dialogData,
    (newVal) => {
        instructionData.value = newVal;
        nameData.value = newVal?.name ?? '';
        keyData.value = newVal?.key ?? '';
        instructionsData.value = newVal?.instructions ?? '';
        enabledData.value = newVal?.enabled ?? true;
        scopeData.value = newVal?.scope ? JSON.stringify(newVal.scope, null, 2) : '';
        typeCreate.value = props.dialogTypeCreate;
    },
    { immediate: true, deep: true },
);

watch(
    () => props.dialogTypeCreate,
    (newVal) => {
        typeCreate.value = newVal;
    },
);

const showDeleted = computed(() => {
    return !typeCreate.value;
});

const postAiModelInstructionManData = async () => {
    try {
        let scopeObj = null;
        if (scopeData.value.trim()) {
            try {
                scopeObj = JSON.parse(scopeData.value);
            } catch {
                // If JSON parsing fails, show error via toast
                throw new Error('Invalid JSON format for scope');
            }
        }

        await api.postAiModelInstructionManSubmit({
            type_create: typeCreate.value ? 1 : 0,
            id: instructionData.value?.id,
            name: nameData.value,
            key: keyData.value,
            instructions: instructionsData.value,
            enabled: enabledData.value,
            scope: scopeObj,
        });
        closeDialogFunction();
    } catch {
        // Toast handled globally by ApiClient
    }
};

const postDeleteAiModelInstructionManData = async () => {
    try {
        const id = instructionData.value?.id as string | number;
        await api.postDeleteAiModelInstructionManSubmit(id);
        closeDialogFunction();
    } catch {
        // Toast handled globally by ApiClient
    }
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Name:<span class="text-red-500 font-bold">*</span></span>
            </div>
            <div class="w-full text-sm">
                <UInput v-model="nameData" class="w-full text-sm" />
            </div>
        </div>

        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Key:<span class="text-red-500 font-bold">*</span></span>
            </div>
            <div class="w-full text-sm">
                <UInput
                    v-model="keyData"
                    class="w-full text-sm"
                    placeholder="e.g., support_agent_prompt"
                />
            </div>
        </div>

        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Instructions:<span class="text-red-500 font-bold">*</span></span>
            </div>
            <div class="w-full text-sm">
                <UTextarea v-model="instructionsData" class="w-full text-sm font-mono" :rows="10" />
                <div class="mt-1 text-xs text-gray-500">
                    ⚠️ <strong>Rule:</strong> Do NOT instruct agents to use direct SQL. Use the
                    application ORM (Eloquent for PHP, GORM for Go) and prefer model/query methods
                    over raw SQL.
                </div>
            </div>
        </div>

        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Scope (JSON):</span>
            </div>
            <div class="w-full text-sm">
                <UTextarea
                    v-model="scopeData"
                    class="w-full text-sm font-mono"
                    :rows="4"
                    placeholder='{"division": "support"}'
                />
            </div>
        </div>

        <div class="flex w-full flex-col gap-1 sm:flex-row sm:items-start">
            <div class="min-w-[8rem] w-32 pt-2 text-sm font-medium text-gray-700">
                <span>Enabled:</span>
            </div>
            <div class="w-full pt-2 text-sm">
                <USwitch v-model="enabledData" />
            </div>
        </div>

        <div class="flex w-full flex-wrap justify-end gap-2 border-t border-gray-200 pt-3">
            <StdButton
                class="flex-1 sm:flex-none"
                variant="primary"
                @click="postAiModelInstructionManData"
            >
                <span>Submit</span>
            </StdButton>
            <StdButton class="flex-1 sm:flex-none" variant="neutral" @click="closeDialogFunction">
                <span>Cancel</span>
            </StdButton>
            <StdButton
                v-if="showDeleted"
                class="flex-1 sm:flex-none"
                variant="danger"
                @click="postDeleteAiModelInstructionManData"
            >
                <span>Delete</span>
            </StdButton>
        </div>
    </div>
</template>
