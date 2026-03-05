<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { api } from '../AppAxios';
import StdButton from '../Components/StdButton.vue';

interface DivisionDataInterface {
    id: string;
    name: string;
    description: string | null;
    enabled: boolean;
}

const props = defineProps<{
    dialogOpen: boolean;
    dialogData: DivisionDataInterface | null;
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

const divisionData = ref<DivisionDataInterface | null>(props.dialogData);
const typeCreate = ref<boolean>(props.dialogTypeCreate);
const nameData = ref<string>(divisionData.value?.name ?? '');
const descriptionData = ref<string>(divisionData.value?.description ?? '');
const enabledData = ref<boolean>(divisionData.value?.enabled ?? true);

// keep local reactive state in sync with prop changes (when editing different rows without remount)
watch(
    () => props.dialogData,
    (newVal) => {
        divisionData.value = newVal;
        nameData.value = newVal?.name ?? '';
        descriptionData.value = newVal?.description ?? '';
        enabledData.value = newVal?.enabled ?? true;
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

const postDivisionManData = async () => {
    try {
        await api.postDivisionManSubmit({
            type_create: typeCreate.value ? 1 : 0,
            id: divisionData.value?.id,
            name: nameData.value,
            description: descriptionData.value,
            enabled: enabledData.value,
        });
        closeDialogFunction();
    } catch {
        // Toast handled globally by ApiClient
    }
};

const postDeleteDivisionManData = async () => {
    try {
        const id = divisionData.value?.id as string | number;
        await api.postDeleteDivisionManSubmit(id);
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
                <span>Description:</span>
            </div>
            <div class="w-full text-sm">
                <UTextarea v-model="descriptionData" class="w-full text-sm" :rows="3" />
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
            <StdButton class="flex-1 sm:flex-none" variant="primary" @click="postDivisionManData">
                <span>Submit</span>
            </StdButton>
            <StdButton class="flex-1 sm:flex-none" variant="neutral" @click="closeDialogFunction">
                <span>Cancel</span>
            </StdButton>
            <StdButton
                v-if="showDeleted"
                class="flex-1 sm:flex-none"
                variant="danger"
                @click="postDeleteDivisionManData"
            >
                <span>Delete</span>
            </StdButton>
        </div>
    </div>
</template>
