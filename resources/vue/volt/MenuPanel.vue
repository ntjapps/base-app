<template>
    <div :class="theme.root + ' ' + theme.transition">
        <Accordion v-model:value="expandedKeysValue">
            <div v-for="(item, index) in model" :key="index">
                <AccordionPanel v-if="item.items?.length > 0" :value="item.key ?? index">
                    <AccordionHeader>{{ item.label }}</AccordionHeader>
                    <AccordionContent
                        v-for="(subItem, subIndex) in item.items"
                        :key="subIndex"
                        :pt="{
                            content:
                                theme.itemLink +
                                ' m-2 ' +
                                theme.itemContent +
                                ' ' +
                                theme.transition,
                        }"
                        @click="gotoPage(subItem.url)"
                    >
                        <i :class="theme.itemIcon + ' pi ' + subItem.icon" />
                        <span :class="theme.item">
                            {{ subItem.label }}
                        </span>
                    </AccordionContent>
                </AccordionPanel>
                <div
                    v-else
                    :class="theme.itemLink + ' m-2 ' + theme.itemContent"
                    @click="gotoPage(item.url)"
                >
                    <i :class="theme.itemIcon + ' pi ' + item.icon" />
                    <span :class="theme.item">
                        {{ item.label }}
                    </span>
                </div>
            </div>
        </Accordion>
    </div>
</template>

<script setup lang="ts">
import { type MenuPassThroughOptions, MenuProps } from 'primevue/menu';
import { computed, ref } from 'vue';
import { ptViewMerge } from './utils';
import Accordion from './Accordion.vue';
import AccordionPanel from './AccordionPanel.vue';
import AccordionHeader from './AccordionHeader.vue';
import AccordionContent from './AccordionContent.vue';

const props = defineProps<
    MenuProps & {
        expandedKeys?: {
            [key: string]: boolean;
        };
    }
>();

// Define emit for two-way binding
const emit = defineEmits(['update:expandedKeys']);

const expandedKeysValue = computed({
    get: () => {
        // If expandedKeys exists, get the first key from the object
        if (props.expandedKeys && Object.keys(props.expandedKeys).length > 0) {
            return Object.keys(props.expandedKeys)[0];
        }
        return '0'; // Default value if no keys exist
    },
    set: (newValue) => {
        // When the Accordion updates its value, create a new object with the selected key
        const updatedKeys = { [newValue]: true };
        emit('update:expandedKeys', updatedKeys);
    },
});

const gotoPage = (url: string) => {
    window.location.href = url;
};

const theme = ref<MenuPassThroughOptions>({
    root: `bg-surface-0 dark:bg-surface-900 
        text-surface-700 dark:text-surface-0 
        border border-surface-200 dark:border-surface-700
        rounded-md min-w-52
        p-popup:shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1),0_2px_4px_-2px_rgba(0,0,0,0.1)] w-full`,
    list: `m-0 p-1 list-none outline-none flex flex-col gap-[2px]`,
    item: `p-disabled:opacity-60 p-disabled:pointer-events-none`,
    itemContent: `group transition-colors duration-200 rounded-sm text-surface-700 dark:text-surface-0
        p-focus:bg-surface-100 dark:p-focus:bg-surface-800 p-focus:text-surface-800 dark:p-focus:text-surface-0
        hover:bg-surface-100 dark:hover:bg-surface-800 hover:text-surface-800 dark:hover:text-surface-0`,
    itemLink: `cursor-pointer flex items-center no-underline overflow-hidden relative text-inherit
        px-3 py-2 gap-2 select-none outline-none`,
    itemIcon: `text-surface-400 dark:text-surface-500
        p-focus:text-surface-500 dark:p-focus:text-surface-400
        group-hover:text-surface-500 dark:group-hover:text-surface-400`,
    itemLabel: ``,
    submenuLabel: `bg-transparent px-3 py-2 text-surface-500 dark:text-surface-400 font-semibold`,
    separator: `border-t border-surface-200 dark:border-surface-700`,
    transition: {
        enterFromClass: 'opacity-0 scale-y-75',
        enterActiveClass: 'transition duration-120 ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0',
    },
});
</script>
