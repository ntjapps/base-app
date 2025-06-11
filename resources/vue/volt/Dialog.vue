<template>
    <Dialog
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
        <template #maximizebutton="{ maximized, maximizeCallback }">
            <SecondaryButton variant="text" rounded @click="maximizeCallback" autofocus>
                <WindowMinimizeIcon v-if="maximized" />
                <WindowMaximizeIcon v-else />
            </SecondaryButton>
        </template>
        <template v-for="(_, slotName) in $slots" v-slot:[slotName]="slotProps">
            <slot :name="slotName" v-bind="slotProps ?? {}" />
        </template>
    </Dialog>
</template>

<script setup lang="ts">
import TimesIcon from '@primevue/icons/times';
import WindowMaximizeIcon from '@primevue/icons/windowmaximize';
import WindowMinimizeIcon from '@primevue/icons/windowminimize';
import Dialog, { type DialogPassThroughOptions, type DialogProps } from 'primevue/dialog';
import { ref } from 'vue';
import SecondaryButton from './SecondaryButton.vue';
import { ptViewMerge } from './utils';

interface Props extends /* @vue-ignore */ DialogProps {}
defineProps<Props>();

const theme = ref<DialogPassThroughOptions>({
    root: `max-h-[90%] w-full max-w-xs sm:max-w-md md:max-w-lg lg:max-w-2xl rounded-lg sm:rounded-xl
        border border-surface-200 dark:border-surface-700
        bg-surface-0 dark:bg-surface-900
        text-surface-700 dark:text-surface-0 shadow-lg
        p-maximized:w-screen p-maximized:h-screen p-maximized:top-0 p-maximized:start-0 p-maximized:max-h-full p-maximized:rounded-none`,
    header: `flex items-center justify-between shrink-0 p-3 sm:p-5`,
    title: `font-semibold text-lg sm:text-xl`,
    headerActions: `flex items-center gap-2`,
    content: `overflow-y-auto pt-0 px-3 sm:px-5 pb-3 sm:pb-5 p-maximized:grow`,
    footer: `shrink-0 pt-0 px-3 sm:px-5 pb-3 sm:pb-5 flex justify-end gap-2`,
    mask: `bg-black/50 fixed top-0 start-0 w-full h-full`,
    transition: {
        enterFromClass: 'opacity-0 scale-75',
        enterActiveClass: 'transition-all duration-150 ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-all duration-150 ease-[cubic-bezier(0.4,0,0.2,1)]',
        leaveToClass: 'opacity-0 scale-75',
    },
});
</script>
