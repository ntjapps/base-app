<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import Select from '../volt/Select.vue';
import InputText from '../volt/InputText.vue';
import Textarea from '../volt/Textarea.vue';

interface ComponentData {
    type: string;
    format?: string;
    text?: string;
    url?: string;
    phone_number?: string;
    example?: Record<string, unknown> | string[] | string;
    buttons?: Array<Record<string, unknown>>;
    flow_id?: string;
    flow_name?: string;
    flow_json?: Record<string, unknown> | null;
    flow_action?: string;
    navigate_screen?: string;
    icon?: string;
}

interface Props {
    modelValue: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

// Header component
const headerType = ref<string>('TEXT');
const headerText = ref<string>('');
const headerExample = ref<string>('');

// Body component
const bodyText = ref<string>('');
const bodyExample = ref<string>('');

// Footer component
const footerText = ref<string>('');

// Buttons
interface ButtonItem {
    type: 'QUICK_REPLY' | 'URL' | 'PHONE_NUMBER' | string;
    text: string;
    url?: string;
    phone_number?: string;
}

const buttons = ref<ButtonItem[]>([]);

// Computed JSON output
const jsonOutput = computed(() => {
    const result: ComponentData[] = [];

    // Add header if configured
    if (headerText.value.trim()) {
        const header: ComponentData = {
            type: 'HEADER',
            format: headerType.value,
        };

        if (headerType.value === 'TEXT') {
            header.text = headerText.value;
            if (headerExample.value.trim()) {
                header.example = {
                    header_text: [headerExample.value],
                };
            }
        }

        result.push(header);
    }

    // Add body (required)
    if (bodyText.value.trim()) {
        const body: ComponentData = {
            type: 'BODY',
            text: bodyText.value,
        };

        if (bodyExample.value.trim()) {
            const examples = bodyExample.value
                .split(',')
                .map((ex) => ex.trim())
                .filter((ex) => ex);
            if (examples.length > 0) {
                body.example = {
                    body_text: [examples],
                };
            }
        }

        result.push(body);
    }

    // Add footer if configured
    if (footerText.value.trim()) {
        result.push({
            type: 'FOOTER',
            text: footerText.value,
        });
    }

    // Add buttons if configured
    if (buttons.value.length > 0) {
        result.push({
            type: 'BUTTONS',
            buttons: buttons.value,
        });
    }

    return JSON.stringify(result, null, 2);
});

// Watch for changes and emit
watch(jsonOutput, (newValue) => {
    emit('update:modelValue', newValue);
});

// Load from existing JSON
const loadFromJson = () => {
    try {
        if (!props.modelValue || props.modelValue.trim() === '[]') {
            // Reset to empty state
            headerType.value = 'TEXT';
            headerText.value = '';
            headerExample.value = '';
            bodyText.value = '';
            bodyExample.value = '';
            footerText.value = '';
            buttons.value = [];
            emit('update:modelValue', jsonOutput.value);
            return;
        }

        const parsed = JSON.parse(props.modelValue);
        if (!Array.isArray(parsed)) return;

        // Reset form
        headerType.value = 'TEXT';
        headerText.value = '';
        headerExample.value = '';
        bodyText.value = '';
        bodyExample.value = '';
        footerText.value = '';
        buttons.value = [];

        // Load components
        parsed.forEach((comp: ComponentData) => {
            if (comp.type === 'HEADER') {
                headerType.value = comp.format || 'TEXT';
                headerText.value = comp.text || '';
                const example = comp.example;
                if (
                    example &&
                    typeof example === 'object' &&
                    Array.isArray((example as Record<string, unknown>)['header_text'])
                ) {
                    const headerArr = (example as Record<string, unknown>)[
                        'header_text'
                    ] as unknown[];
                    if (headerArr[0] && typeof headerArr[0] === 'string') {
                        headerExample.value = headerArr[0];
                    }
                }
            } else if (comp.type === 'BODY') {
                bodyText.value = comp.text || '';
                const example = comp.example;
                if (
                    example &&
                    typeof example === 'object' &&
                    Array.isArray((example as Record<string, unknown>)['body_text']) &&
                    Array.isArray(
                        ((example as Record<string, unknown>)['body_text'] as unknown[])[0],
                    )
                ) {
                    const arr = (
                        (example as Record<string, unknown>)['body_text'] as unknown[]
                    )[0] as unknown[];
                    bodyExample.value = arr.map((v) => String(v)).join(', ');
                }
            } else if (comp.type === 'FOOTER') {
                footerText.value = comp.text || '';
            } else if (comp.type === 'BUTTONS') {
                const rawButtons = comp.buttons;
                if (Array.isArray(rawButtons)) {
                    // coerce entries to ButtonItem where possible
                    buttons.value = rawButtons.map((b) => {
                        const obj = b as Record<string, unknown>;
                        return {
                            type: String(obj.type ?? ''),
                            text: String(obj.text ?? ''),
                            url: obj.url ? String(obj.url) : undefined,
                            phone_number: obj.phone_number ? String(obj.phone_number) : undefined,
                        } as ButtonItem;
                    });
                } else {
                    buttons.value = [];
                }
            }
        });
    } catch {
        console.error('Invalid JSON format, resetting to empty state');
        // Reset to empty state on error
        headerType.value = 'TEXT';
        headerText.value = '';
        headerExample.value = '';
        bodyText.value = '';
        bodyExample.value = '';
        footerText.value = '';
        buttons.value = [];
    emit('update:modelValue', jsonOutput.value);
    }
};

// Watch for prop changes to load initial data
watch(() => props.modelValue, loadFromJson, { immediate: true });

// Add button
const addButton = (type: ButtonItem['type']) => {
    const button: ButtonItem = { type, text: '' };

    if (type === 'URL') {
        button.url = '';
    } else if (type === 'PHONE_NUMBER') {
        button.phone_number = '';
    }

    buttons.value.push(button);
};

// Remove button
const removeButton = (index: number) => {
    buttons.value.splice(index, 1);
};
</script>

<template>
    <div class="space-y-4">
        <!-- Header Section -->
        <div class="border border-surface-200 dark:border-surface-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold mb-3">Header (Optional)</h3>

            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Header Type</label>
                    <Select
                        v-model="headerType"
                        :options="[
                            { code: 'TEXT', label: 'Text' },
                            { code: 'IMAGE', label: 'Image' },
                            { code: 'VIDEO', label: 'Video' },
                            { code: 'DOCUMENT', label: 'Document' },
                            { code: 'LOCATION', label: 'Location' },
                        ]"
                        optionLabel="label"
                        optionValue="code"
                    />
                </div>

                <div v-if="headerType === 'TEXT'">
                    <label class="block text-sm font-medium mb-1">Header Text</label>
                    <InputText
                        v-model="headerText"
                        placeholder="Enter header text"
                        class="w-full"
                    />
                </div>

                <div v-if="headerType === 'TEXT' && headerText.includes('{{1}}')">
                    <label class="block text-sm font-medium mb-1">Header Example</label>
                    <InputText
                        v-model="headerExample"
                        placeholder="Example value for {{1}}"
                        class="w-full"
                    />
                </div>
            </div>
        </div>

        <!-- Body Section (Required) -->
        <div class="border border-surface-200 dark:border-surface-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold mb-3">Body (Required)</h3>

            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Body Text</label>
                    <Textarea
                        v-model="bodyText"
                        placeholder="Enter body text with {{1}}, {{2}} for variables"
                        rows="3"
                        class="w-full"
                    />
                </div>

                <div v-if="bodyText.includes('{{')">
                    <label class="block text-sm font-medium mb-1"
                        >Body Examples (comma-separated for multiple parameters)</label
                    >
                    <InputText
                        v-model="bodyExample"
                        placeholder="value1,value2,value3"
                        class="w-full"
                    />
                    <small class="text-gray-500"
                        >Use commas to separate values for {{ 1 }}, {{ 2 }}, etc.</small
                    >
                </div>
            </div>
        </div>

        <!-- Footer Section -->
        <div class="border border-surface-200 dark:border-surface-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold mb-3">Footer (Optional)</h3>

            <div>
                <label class="block text-sm font-medium mb-1">Footer Text</label>
                <InputText v-model="footerText" placeholder="Enter footer text" class="w-full" />
            </div>
        </div>

        <!-- Buttons Section -->
        <div class="border border-surface-200 dark:border-surface-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold mb-3">Buttons (Optional)</h3>

            <div class="space-y-3">
                <div class="flex gap-2 flex-wrap">
                    <UButton size="sm" @click="addButton('QUICK_REPLY')">Add Quick Reply</UButton>
                    <UButton size="sm" @click="addButton('URL')">Add URL Button</UButton>
                    <UButton size="sm" @click="addButton('PHONE_NUMBER')">Add Phone Button</UButton>
                </div>

                <div
                    v-for="(button, index) in buttons"
                    :key="index"
                    class="border border-surface-100 dark:border-surface-800 rounded p-3"
                >
                    <div class="flex justify-between items-start mb-2">
                        <span class="font-medium">{{ button.type.replace('_', ' ') }}</span>
                        <UButton size="sm" severity="danger" @click="removeButton(index)"
                            >Remove</UButton
                        >
                    </div>

                    <div class="space-y-2">
                        <div>
                            <label class="block text-sm mb-1">Button Text</label>
                            <InputText
                                v-model="button.text"
                                placeholder="Button text"
                                class="w-full"
                            />
                        </div>

                        <div v-if="button.type === 'URL'">
                            <label class="block text-sm mb-1">URL</label>
                            <InputText
                                v-model="button.url"
                                placeholder="https://example.com"
                                class="w-full"
                            />
                        </div>

                        <div v-if="button.type === 'PHONE_NUMBER'">
                            <label class="block text-sm mb-1">Phone Number</label>
                            <InputText
                                v-model="button.phone_number"
                                placeholder="+1234567890"
                                class="w-full"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generated JSON Preview -->
        <div class="border border-surface-200 dark:border-surface-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold mb-3">Generated JSON</h3>
            <Textarea :modelValue="jsonOutput" readonly rows="8" class="w-full font-mono text-sm" />
        </div>
    </div>
</template>
