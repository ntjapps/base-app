<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import Select from '../volt/Select.vue';
import InputText from '../volt/InputText.vue';
import CmpComponentBuilder from './CmpComponentBuilder.vue';
import { api } from '../AppAxios';

interface DialogData {
    id?: string;
    name?: string;
    language?: string;
    status?: string;
    category?: string;
    components?: unknown[];
    correct_category?: string;
    cta_url_link_tracking_opted_out?: boolean;
    degrees_of_freedom_spec?: unknown;
    library_template_name?: string;
    message_send_ttl_seconds?: number;
    parameter_format?: string;
    previous_category?: string;
    quality_score?: unknown;
    rejected_reason?: string;
    sub_category?: string;
}

const props = defineProps<{
    dialogOpen: boolean;
    dialogData: DialogData | null;
    mode: 'create' | 'edit';
}>();
const emit = defineEmits<{
    (e: 'closeDialog'): void;
    (e: 'update:dialogOpen', value: boolean): void;
    (e: 'saved'): void;
}>();

const loading = ref(false);

const name = ref<string>('');
const language = ref<string>('en_US');
const category = ref<string>('AUTHENTICATION');
const components = ref<string>('[]');
const messageSendTtlSeconds = ref<number | null>(null);
const ctaUrlLinkTrackingOptedOut = ref<boolean>(false);

// Complete list of WhatsApp Business supported languages
const languages = ref<Array<{ code: string; label: string }>>([
    { code: 'af', label: 'Afrikaans' },
    { code: 'sq', label: 'Albanian' },
    { code: 'ar', label: 'Arabic' },
    { code: 'ar_EG', label: 'Arabic (EGY)' },
    { code: 'ar_AE', label: 'Arabic (UAE)' },
    { code: 'ar_LB', label: 'Arabic (LBN)' },
    { code: 'ar_MA', label: 'Arabic (MAR)' },
    { code: 'ar_QA', label: 'Arabic (QAT)' },
    { code: 'az', label: 'Azerbaijani' },
    { code: 'be_BY', label: 'Belarusian' },
    { code: 'bn', label: 'Bengali' },
    { code: 'bn_IN', label: 'Bengali (IND)' },
    { code: 'bg', label: 'Bulgarian' },
    { code: 'ca', label: 'Catalan' },
    { code: 'zh_CN', label: 'Chinese (CHN)' },
    { code: 'zh_HK', label: 'Chinese (HKG)' },
    { code: 'zh_TW', label: 'Chinese (TAI)' },
    { code: 'hr', label: 'Croatian' },
    { code: 'cs', label: 'Czech' },
    { code: 'da', label: 'Danish' },
    { code: 'prs_AF', label: 'Dari' },
    { code: 'nl', label: 'Dutch' },
    { code: 'nl_BE', label: 'Dutch (BEL)' },
    { code: 'en', label: 'English' },
    { code: 'en_GB', label: 'English (UK)' },
    { code: 'en_US', label: 'English (US)' },
    { code: 'en_AE', label: 'English (UAE)' },
    { code: 'en_AU', label: 'English (AUS)' },
    { code: 'en_CA', label: 'English (CAN)' },
    { code: 'en_GH', label: 'English (GHA)' },
    { code: 'en_IE', label: 'English (IRL)' },
    { code: 'en_IN', label: 'English (IND)' },
    { code: 'en_JM', label: 'English (JAM)' },
    { code: 'en_MY', label: 'English (MYS)' },
    { code: 'en_NZ', label: 'English (NZL)' },
    { code: 'en_QA', label: 'English (QAT)' },
    { code: 'en_SG', label: 'English (SGP)' },
    { code: 'en_UG', label: 'English (UGA)' },
    { code: 'en_ZA', label: 'English (ZAF)' },
    { code: 'et', label: 'Estonian' },
    { code: 'fil', label: 'Filipino' },
    { code: 'fi', label: 'Finnish' },
    { code: 'fr', label: 'French' },
    { code: 'fr_BE', label: 'French (BEL)' },
    { code: 'fr_CA', label: 'French (CAN)' },
    { code: 'fr_CH', label: 'French (CHE)' },
    { code: 'fr_CI', label: 'French (CIV)' },
    { code: 'fr_MA', label: 'French (MAR)' },
    { code: 'ka', label: 'Georgian' },
    { code: 'de', label: 'German' },
    { code: 'de_AT', label: 'German (AUT)' },
    { code: 'de_CH', label: 'German (CHE)' },
    { code: 'el', label: 'Greek' },
    { code: 'gu', label: 'Gujarati' },
    { code: 'ha', label: 'Hausa' },
    { code: 'he', label: 'Hebrew' },
    { code: 'hi', label: 'Hindi' },
    { code: 'hu', label: 'Hungarian' },
    { code: 'id', label: 'Indonesian' },
    { code: 'ga', label: 'Irish' },
    { code: 'it', label: 'Italian' },
    { code: 'ja', label: 'Japanese' },
    { code: 'kn', label: 'Kannada' },
    { code: 'kk', label: 'Kazakh' },
    { code: 'rw_RW', label: 'Kinyarwanda' },
    { code: 'ko', label: 'Korean' },
    { code: 'ky_KG', label: 'Kyrgyz (Kyrgyzstan)' },
    { code: 'lo', label: 'Lao' },
    { code: 'lv', label: 'Latvian' },
    { code: 'lt', label: 'Lithuanian' },
    { code: 'mk', label: 'Macedonian' },
    { code: 'ms', label: 'Malay' },
    { code: 'ml', label: 'Malayalam' },
    { code: 'mr', label: 'Marathi' },
    { code: 'nb', label: 'Norwegian' },
    { code: 'ps_AF', label: 'Pashto' },
    { code: 'fa', label: 'Persian' },
    { code: 'pl', label: 'Polish' },
    { code: 'pt_BR', label: 'Portuguese (BR)' },
    { code: 'pt_PT', label: 'Portuguese (POR)' },
    { code: 'pa', label: 'Punjabi' },
    { code: 'ro', label: 'Romanian' },
    { code: 'ru', label: 'Russian' },
    { code: 'sr', label: 'Serbian' },
    { code: 'si_LK', label: 'Sinhala' },
    { code: 'sk', label: 'Slovak' },
    { code: 'sl', label: 'Slovenian' },
    { code: 'es', label: 'Spanish' },
    { code: 'es_AR', label: 'Spanish (ARG)' },
    { code: 'es_CL', label: 'Spanish (CHL)' },
    { code: 'es_CO', label: 'Spanish (COL)' },
    { code: 'es_CR', label: 'Spanish (CRI)' },
    { code: 'es_DO', label: 'Spanish (DOM)' },
    { code: 'es_EC', label: 'Spanish (ECU)' },
    { code: 'es_HN', label: 'Spanish (HND)' },
    { code: 'es_MX', label: 'Spanish (MEX)' },
    { code: 'es_PA', label: 'Spanish (PAN)' },
    { code: 'es_PE', label: 'Spanish (PER)' },
    { code: 'es_ES', label: 'Spanish (SPA)' },
    { code: 'es_UY', label: 'Spanish (URY)' },
    { code: 'sw', label: 'Swahili' },
    { code: 'sv', label: 'Swedish' },
    { code: 'ta', label: 'Tamil' },
    { code: 'te', label: 'Telugu' },
    { code: 'th', label: 'Thai' },
    { code: 'tr', label: 'Turkish' },
    { code: 'uk', label: 'Ukrainian' },
    { code: 'ur', label: 'Urdu' },
    { code: 'uz', label: 'Uzbek' },
    { code: 'vi', label: 'Vietnamese' },
    { code: 'zu', label: 'Zulu' },
]);

watch(
    () => props.dialogOpen,
    (newVal) => {
        emit('update:dialogOpen', newVal);
        if (newVal) {
            loadData();
        } else {
            clearForm();
        }
    },
);

const clearForm = () => {
    name.value = '';
    language.value = 'en_US';
    category.value = 'AUTHENTICATION';
    components.value = '[]';
    messageSendTtlSeconds.value = null;
    ctaUrlLinkTrackingOptedOut.value = false;
};

const loadData = () => {
    const dd = props.dialogData;
    if (!dd || !dd.id) return;
    // since meta API doesn't expose detail, we only prefill with known fields from list
    name.value = dd.name ?? '';
    language.value = dd.language ?? 'en_US';
    category.value = dd.category ?? 'AUTHENTICATION';
    components.value = JSON.stringify((dd.components ?? []) as unknown[], null, 2);
    messageSendTtlSeconds.value = dd.message_send_ttl_seconds ?? null;
    ctaUrlLinkTrackingOptedOut.value = dd.cta_url_link_tracking_opted_out ?? false;
};

const close = () => {
    emit('closeDialog');
    emit('update:dialogOpen', false);
};

const save = async () => {
    try {
        loading.value = true;
        const payload = {
            name: name.value,
            language: language.value,
            category: category.value,
            components: JSON.parse(components.value || '[]'),
            message_send_ttl_seconds: messageSendTtlSeconds.value,
            cta_url_link_tracking_opted_out: ctaUrlLinkTrackingOptedOut.value,
        };

        if (props.mode === 'create') {
            await api.postCreateWhatsappTemplate(payload);
            // ApiClient will show success toast based on standard API response
        } else if (props.mode === 'edit' && props.dialogData?.id) {
            await api.postUpdateWhatsappTemplate(props.dialogData.id, payload);
            // ApiClient will show success toast based on standard API response
        }

        emit('saved');
        close();
    } catch (error) {
        // ApiClient handles error toasts centrally; log for diagnostics

        console.error(error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (props.dialogOpen) loadData();
});
</script>

<template>
    <div class="max-h-[70vh] overflow-y-auto">
        <div class="p-2 space-y-4">
            <div class="mb-2">
                <label class="block text-sm">Name</label>
                <InputText v-model="name" class="w-full" />
            </div>
            <div class="mb-2">
                <label class="block text-sm">Language</label>
                <Select
                    v-model="language"
                    :options="languages"
                    optionLabel="label"
                    optionValue="code"
                    filter
                    placeholder="Search and select language"
                />
            </div>
            <div class="mb-2">
                <label class="block text-sm">Category</label>
                <Select
                    v-model="category"
                    :options="[
                        { code: 'AUTHENTICATION', label: 'AUTHENTICATION' },
                        { code: 'MARKETING', label: 'MARKETING' },
                        { code: 'UTILITY', label: 'UTILITY' },
                    ]"
                    optionLabel="label"
                    optionValue="code"
                />
            </div>
            <div class="mb-2">
                <label class="block text-sm">Components</label>
                <CmpComponentBuilder v-model="components" />
            </div>
            <div class="mb-2">
                <label class="block text-sm">Message Send TTL Seconds</label>
                <InputText v-model.number="messageSendTtlSeconds" class="w-full" type="number" />
            </div>
            <div class="mb-2">
                <div class="flex items-center space-x-2">
                    <UCheckbox v-model="ctaUrlLinkTrackingOptedOut" size="md" />
                    <label class="text-sm">CTA URL Link Tracking Opted Out</label>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-3">
                <UButton size="md" label="Cancel" severity="secondary" @click="close" />
                <UButton size="md" label="Save" :loading="loading" @click="save" />
            </div>
        </div>
    </div>
</template>
