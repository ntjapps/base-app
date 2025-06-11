<template>
    <Drawer
        unstyled
        :pt="theme"
        :ptOptions="{
            mergeProps: ptViewMerge,
        }"
    >
        <template #closebutton="{ closeCallback }">
            <SecondaryButton variant="text" rounded @click="closeCallback" autofocus>
                <TimesIcon />
            </SecondaryButton>
        </template>
        <template v-for="(_, slotName) in $slots" v-slot:[slotName]="slotProps">
            <slot :name="slotName" v-bind="slotProps ?? {}" />
        </template>
    </Drawer>
</template>

<script setup lang="ts">
import TimesIcon from '@primevue/icons/times';
import Drawer, { type DrawerPassThroughOptions, type DrawerProps } from 'primevue/drawer';
import { ref } from 'vue';
import SecondaryButton from './SecondaryButton.vue';
import { ptViewMerge } from './utils';

interface Props extends /* @vue-ignore */ DrawerProps {}
defineProps<Props>();

const theme = ref<DrawerPassThroughOptions>({
    root: `flex flex-col pointer-events-auto relative
        border-surface-200 dark:border-surface-700
        bg-surface-0 dark:bg-surface-900
        text-surface-700 dark:text-surface-0 
        shadow-[0_20px_25px_-5px_rgba(0,0,0,0.1),0_8px_10px_-6px_rgba(0,0,0,0.1)]
        w-4/5 max-w-xs md:w-80 h-full border-r`,
    header: `flex items-center justify-between flex-shrink-0 p-4 md:p-5`,
    title: `font-semibold text-xl md:text-2xl`,
    content: `overflow-y-auto flex-grow pt-0 pb-4 md:pb-5 px-3 md:px-5`,
    footer: `p-4 md:p-5`,
    mask: `p-modal:bg-black/50`,
    transition: {
        enterFromClass: `-translate-x-full`,
        enterActiveClass: `transition-transform duration-400 ease-out`,
        leaveActiveClass: `transition-transform duration-200 ease-in`,
        leaveToClass: `-translate-x-full`,
    },
});
</script>
