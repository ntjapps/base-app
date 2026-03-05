<script setup lang="ts">
import { computed, useAttrs } from 'vue';

/**
 * StdButton
 * A small wrapper around `UButton` that provides application-wide standard
 * defaults for `color`, `icon`, `size` and spacing via a `variant` prop.
 *
 * Props:
 * - variant: 'primary' | 'danger' | 'warn' | 'neutral' | 'success' (default: 'primary')
 * - color/icon/size/label: can override the mapped defaults
 * - any other attributes (including `class`) are forwarded to `UButton`
 */

type StdButtonProps = {
    variant?: 'primary' | 'danger' | 'warn' | 'neutral' | 'success';
    color?: string;
    icon?: string;
    size?: string;
    label?: string;
};

const props = withDefaults(defineProps<StdButtonProps>(), {
    variant: 'primary',
    color: '',
    icon: '',
    size: 'xl',
    label: '',
});

const attrs = useAttrs();

const mapping: Record<string, { color: string; icon?: string }> = {
    primary: { color: 'primary', icon: 'i-heroicons-check' },
    danger: { color: 'error', icon: 'i-heroicons-trash' },
    warn: { color: 'warning', icon: 'i-heroicons-lock-closed' },
    neutral: { color: 'neutral', icon: '' },
    success: { color: 'success', icon: 'i-heroicons-check' },
};

const resolved = computed(() => {
    const map = mapping[props.variant] ?? mapping.primary;
    return {
        color: (props.color || map.color) as string,
        icon: (props.icon || map.icon) as string,
        size: props.size as string,
        // merge default spacing with any user-provided class
        classes: ['m-1 md:m-2', (attrs.class as string) || ''],
    };
});
</script>

<template>
    <UButton
        :color="resolved.color"
        :icon="resolved.icon"
        :size="resolved.size"
        :label="label"
        v-bind="attrs"
        :class="resolved.classes"
    >
        <template v-if="$slots.default">
            <slot />
        </template>
    </UButton>
</template>
