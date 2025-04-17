<template>
    <Message
        unstyled
        :pt="theme"
        :ptOptions="{
            mergeProps: ptViewMerge,
        }"
    >
        <template #closeicon>
            <TimesIcon />
        </template>
        <template v-for="(_, slotName) in $slots" v-slot:[slotName]="slotProps">
            <slot :name="slotName" v-bind="slotProps ?? {}" />
        </template>
    </Message>
</template>

<script setup lang="ts">
import TimesIcon from '@primevue/icons/times';
import Message, { type MessagePassThroughOptions, type MessageProps } from 'primevue/message';
import { ref } from 'vue';
import { ptViewMerge } from './utils';

interface Props extends /* @vue-ignore */ MessageProps {}
defineProps<Props>();

const theme = ref<MessagePassThroughOptions>({
    root: `rounded-md bg-transparent outline-none`,
    content: `flex items-center
        p-0
        px-2.5 py-[0.375rem]`,
    icon: `flex-shrink-0 text-lg w-[0.875rem] h-[0.875rem] text-sm`,
    text: `text-sm`,
    closeButton: `flex items-center justify-center flex-shrink-0 ms-auto overflow-hidden relative cursor-pointer select-none
        w-7 h-7 rounded-full bg-transparent transition-colors duration-200 text-inherit p-0 border-none`,
    closeIcon: `w-3.5 h-3.5 text-sm`,
    transition: {
        enterFromClass: 'opacity-0',
        enterActiveClass: 'transition-opacity duration-300',
        leaveFromClass: 'max-h-40',
        leaveActiveClass: 'overflow-hidden transition-all duration-300 ease-in',
        leaveToClass: 'max-h-0 opacity-0 !m-0',
    },
});
</script>
