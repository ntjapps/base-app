<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useHead } from '@unhead/vue';
import { storeToRefs } from 'pinia';
import { useMainStore } from '../AppState';
import mainLogo from '../../images/Main Logo.webp';

type Locale = 'en' | 'id';

const main = useMainStore();
const { appName } = storeToRefs(main);

const mobileMenuOpen = ref(false);
const showScrollTop = ref(false);
const language = ref<Locale>('en');

const isEnglish = computed(() => language.value === 'en');

useHead(
    computed(() => {
        const name = appName.value || import.meta.env.VITE_APP_NAME || 'Application';
        const title = isEnglish.value
            ? `Terms of Service (App) | ${name}`
            : `Syarat dan Ketentuan App | ${name}`;
        const description = isEnglish.value
            ? 'Terms of Service for App, including service scope, account obligations, acceptable use, payment terms, and liability limits.'
            : 'Syarat dan Ketentuan App, termasuk ruang lingkup layanan, kewajiban akun, penggunaan yang diperbolehkan, ketentuan pembayaran, dan batasan tanggung jawab.';

        return {
            title,
            meta: [
                { name: 'description', content: description },
                { property: 'og:title', content: title },
                { property: 'og:description', content: description },
                { property: 'og:type', content: 'website' },
                { property: 'og:locale', content: isEnglish.value ? 'en_US' : 'id_ID' },
            ],
        };
    }),
);

const labels = computed(() => {
    const name = appName.value || import.meta.env.VITE_APP_NAME || 'Application';
    if (isEnglish.value) {
        return {
            backHome: 'Back to Home',
            terms: `Terms of Service for App by ${name}`,
            company: 'Company',
            legal: 'Legal',
            home: 'Home',
            solutions: 'Solutions',
            contact: 'Contact',
            privacyPolicyMain: 'Privacy Policy',
            termsOfServiceMain: 'Terms of Service',
            privacyPolicy: 'Privacy Policy (App)',
            termsOfService: 'Terms of Service (App)',
            aiEthics: 'AI Ethics',
            trustedPartner:
                'Your trusted partner for enterprise software innovation and digital transformation.',
            rightsReserved: 'All rights reserved.',
            aiCompliance: 'AI-Assisted & Human-Reviewed',
            lastUpdated: 'Last Updated: March 5, 2026',
        };
    }

    return {
        backHome: 'Kembali ke Beranda',
        terms: `Syarat dan Ketentuan App oleh ${name}`,
        company: 'Perusahaan',
        legal: 'Legal',
        home: 'Beranda',
        solutions: 'Solusi',
        contact: 'Kontak',
        privacyPolicyMain: 'Kebijakan Privasi',
        termsOfServiceMain: 'Syarat dan Ketentuan Layanan',
        privacyPolicy: 'Kebijakan Privasi (App)',
        termsOfService: 'Syarat dan Ketentuan (App)',
        aiEthics: 'Etika AI',
        trustedPartner:
            'Mitra tepercaya Anda untuk inovasi perangkat lunak enterprise dan transformasi digital.',
        rightsReserved: 'Seluruh hak dilindungi.',
        aiCompliance: 'Dibantu AI & Ditinjau Manusia',
        lastUpdated: 'Terakhir Diperbarui: 5 Maret 2026',
    };
});

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const setLanguage = (locale: Locale) => {
    language.value = locale;
    if (typeof window !== 'undefined') {
        window.localStorage.setItem('site_language', locale);
        const url = new URL(window.location.href);
        url.searchParams.set('lang', locale);
        window.history.replaceState({}, '', url.toString());
    }
};

const localizedPath = (path: string): string => `${path}?lang=${language.value}`;

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const queryLang = params.get('lang');
    const savedLang = window.localStorage.getItem('site_language');

    if (queryLang === 'id' || queryLang === 'en') {
        language.value = queryLang;
    } else if (savedLang === 'id' || savedLang === 'en') {
        language.value = savedLang;
    }

    window.addEventListener('scroll', () => {
        showScrollTop.value = window.scrollY > 300;
    });
});
</script>

<template>
    <div
        class="terms-of-service min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-cyan-100 selection:text-cyan-900"
    >
        <header
            class="bg-white/90 backdrop-blur-md sticky top-0 w-full z-50 border-b border-slate-200"
        >
            <div
                class="container mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center"
            >
                <a :href="localizedPath('/')" class="flex items-center gap-2 group">
                    <img :src="mainLogo" :alt="appName || 'Logo'" class="h-10 w-auto" />
                    <span
                        class="text-xl font-bold tracking-tight text-slate-900 group-hover:text-cyan-600 transition-colors"
                    >
                        {{ appName }}<span class="text-cyan-600">.</span>
                    </span>
                </a>

                <div class="hidden md:flex items-center gap-6">
                    <nav class="flex items-center space-x-8">
                        <a
                            :href="localizedPath('/')"
                            class="text-sm font-medium text-slate-600 hover:text-cyan-600 transition-colors"
                        >
                            {{ labels.backHome }}
                        </a>
                    </nav>
                    <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1">
                        <button
                            type="button"
                            class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors"
                            :class="
                                language === 'en'
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-600'
                            "
                            @click="setLanguage('en')"
                        >
                            EN
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors"
                            :class="
                                language === 'id'
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-600'
                            "
                            @click="setLanguage('id')"
                        >
                            ID
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    class="md:hidden p-2 text-slate-600 hover:text-slate-900"
                    @click="toggleMobileMenu"
                >
                    <UIcon name="i-heroicons-bars-3" class="w-6 h-6" />
                </button>
            </div>

            <div
                v-show="mobileMenuOpen"
                class="md:hidden bg-white border-t border-slate-200 shadow-xl absolute w-full"
            >
                <nav class="flex flex-col px-4 py-6 space-y-4">
                    <a
                        :href="localizedPath('/')"
                        class="text-base font-medium text-slate-600 hover:text-cyan-600"
                    >
                        {{ labels.backHome }}
                    </a>
                    <div
                        class="inline-flex w-full rounded-lg border border-slate-200 bg-slate-100 p-1"
                    >
                        <button
                            type="button"
                            class="flex-1 px-3 py-2 text-xs font-semibold rounded-md transition-colors"
                            :class="
                                language === 'en'
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-600'
                            "
                            @click="setLanguage('en')"
                        >
                            EN
                        </button>
                        <button
                            type="button"
                            class="flex-1 px-3 py-2 text-xs font-semibold rounded-md transition-colors"
                            :class="
                                language === 'id'
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-600'
                            "
                            @click="setLanguage('id')"
                        >
                            ID
                        </button>
                    </div>
                </nav>
            </div>
        </header>

        <main class="pt-12 pb-24">
            <section class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-4xl">
                <div class="bg-white p-8 md:p-12 rounded-2xl shadow-sm border border-slate-200">
                    <h1 class="text-3xl md:text-4xl font-bold text-center text-slate-900 mb-8">
                        {{ labels.terms }}
                    </h1>
                    <p class="text-slate-500 text-center mb-12">{{ labels.lastUpdated }}</p>

                    <div v-if="isEnglish" class="prose prose-slate max-w-none text-slate-600">
                        <p class="mb-6 leading-relaxed">
                            These Terms of Service ("Terms") govern your use of the Application,
                            including WhatsApp-integrated messaging workflows, related dashboards,
                            and support services (collectively, the "Service"). By using the
                            Service, you agree to these Terms.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">1. Definitions</h3>
                        <p class="mb-6 leading-relaxed">
                            "Provider" means the service operator. "Customer" means a person or
                            entity that subscribes to the Service. "End User" means a message
                            recipient or contact interacting through the Service.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">2. Service Scope</h3>
                        <p class="mb-6 leading-relaxed">
                            The Service helps customers operate WhatsApp business communication.
                            Certain capabilities depend on third-party platforms (including
                            Meta/WhatsApp) and may change according to those platform rules.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            3. Account and Security Obligations
                        </h3>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li>You must provide accurate registration and billing information.</li>
                            <li>You are responsible for account credentials and access control.</li>
                            <li>
                                You must promptly notify us of unauthorized access or suspected
                                abuse.
                            </li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            4. Acceptable Use
                        </h3>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li>
                                You must comply with applicable laws, UU PDP, and platform policies.
                            </li>
                            <li>
                                You may not use the Service for unlawful spam, fraud, harassment, or
                                misleading communications.
                            </li>
                            <li>
                                You may not reverse engineer, disrupt, or misuse platform
                                integrations.
                            </li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            5. Data Processing and Privacy
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Personal data processing under the Service is governed by our WaAgent
                            Privacy Policy at
                            <a
                                :href="localizedPath('/privacy-policy-waagent')"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors"
                                >/privacy-policy-waagent</a
                            >.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            6. Fees and Payment
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Subscription or usage fees are billed according to your plan. Charges
                            from third-party channels may apply separately. Late or failed payment
                            may result in service suspension.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            7. Intellectual Property
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            The Service software, infrastructure, and product materials remain our
                            property or licensors' property. Customer data remains owned by the
                            customer, subject to rights required to operate the Service.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            8. Suspension and Termination
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            We may suspend or terminate access for material breach, legal
                            requirements, security risk, or persistent payment failure, with notice
                            where reasonably practicable.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            9. Limitation of Liability
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            The Service is provided on an "as is" and "as available" basis to the
                            extent permitted by law. We are not liable for indirect or consequential
                            losses, and our aggregate liability is limited as allowed by applicable
                            law.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            10. Changes to Terms
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            We may update these Terms from time to time. Continued use of the
                            Service after updates means you accept the revised Terms.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            11. Governing Law
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            These Terms are governed by the laws of the Republic of Indonesia.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">12. Contact</h3>
                        <p class="mb-6 leading-relaxed">
                            Questions about these Terms can be submitted to
                            <a
                                href="mailto:admin@yourdomain.com"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors"
                                >admin@yourdomain.com</a
                            >.
                        </p>

                        <p class="mb-0 leading-relaxed">
                            <strong>Language Clause:</strong> These Terms are available in English
                            and Indonesian. Both versions are intended to carry equal legal meaning.
                            For legal interpretation and enforcement under Indonesian law, the
                            Indonesian version prevails where required by applicable law.
                        </p>
                    </div>

                    <div v-else class="prose prose-slate max-w-none text-slate-600">
                        <p class="mb-6 leading-relaxed">
                            Syarat dan Ketentuan Layanan ini ("Syarat") mengatur penggunaan Aplikasi
                            ini, termasuk alur pesan terintegrasi WhatsApp, dashboard terkait, dan
                            layanan dukungan (secara bersama disebut "Layanan"). Dengan menggunakan
                            Layanan, Anda menyetujui Syarat ini.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">1. Definisi</h3>
                        <p class="mb-6 leading-relaxed">
                            "Penyedia" berarti operator layanan. "Pelanggan" berarti orang atau
                            badan hukum yang berlangganan Layanan. "Pengguna Akhir" berarti penerima
                            pesan atau kontak yang berinteraksi melalui Layanan.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            2. Ruang Lingkup Layanan
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Layanan membantu pelanggan menjalankan komunikasi bisnis melalui
                            WhatsApp. Beberapa kemampuan bergantung pada platform pihak ketiga
                            (termasuk Meta/WhatsApp) dan dapat berubah mengikuti ketentuan platform
                            tersebut.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            3. Kewajiban Akun dan Keamanan
                        </h3>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li>
                                Anda wajib memberikan data registrasi dan penagihan yang akurat.
                            </li>
                            <li>
                                Anda bertanggung jawab atas kerahasiaan kredensial dan kontrol akses
                                akun.
                            </li>
                            <li>
                                Anda wajib segera memberitahukan akses tidak sah atau dugaan
                                penyalahgunaan.
                            </li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            4. Penggunaan yang Diperbolehkan
                        </h3>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li>
                                Anda wajib mematuhi hukum yang berlaku, UU PDP, dan kebijakan
                                platform.
                            </li>
                            <li>
                                Anda dilarang menggunakan Layanan untuk spam ilegal, penipuan,
                                pelecehan, atau komunikasi menyesatkan.
                            </li>
                            <li>
                                Anda dilarang melakukan reverse engineering, mengganggu, atau
                                menyalahgunakan integrasi platform.
                            </li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            5. Pemrosesan Data dan Privasi
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Pemrosesan data pribadi pada Layanan ini diatur dalam Kebijakan Privasi
                            WaAgent kami di
                            <a
                                :href="localizedPath('/privacy-policy-waagent')"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors"
                                >/privacy-policy-waagent</a
                            >.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            6. Biaya dan Pembayaran
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Biaya berlangganan atau penggunaan ditagihkan sesuai paket Anda. Biaya
                            dari kanal pihak ketiga dapat berlaku terpisah. Keterlambatan atau
                            kegagalan pembayaran dapat menyebabkan penangguhan layanan.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            7. Hak Kekayaan Intelektual
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Perangkat lunak, infrastruktur, dan materi produk pada Layanan tetap
                            menjadi milik kami atau pemberi lisensi kami. Data pelanggan tetap
                            menjadi milik pelanggan, dengan hak terbatas yang diperlukan untuk
                            menjalankan Layanan.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            8. Penangguhan dan Penghentian
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Kami dapat menangguhkan atau menghentikan akses atas pelanggaran
                            material, kewajiban hukum, risiko keamanan, atau kegagalan pembayaran
                            berulang, dengan pemberitahuan sepanjang memungkinkan.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            9. Batasan Tanggung Jawab
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Layanan disediakan dalam kondisi "sebagaimana adanya" dan "sebagaimana
                            tersedia" sepanjang diperbolehkan hukum. Kami tidak bertanggung jawab
                            atas kerugian tidak langsung atau konsekuensial, dan batas tanggung
                            jawab total kami mengikuti ketentuan hukum yang berlaku.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            10. Perubahan Syarat
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Kami dapat mengubah Syarat ini dari waktu ke waktu. Penggunaan Layanan
                            secara berkelanjutan setelah pembaruan berarti Anda menyetujui Syarat
                            yang telah diperbarui.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            11. Hukum yang Berlaku
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Syarat ini tunduk pada hukum Republik Indonesia.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">12. Kontak</h3>
                        <p class="mb-6 leading-relaxed">
                            Pertanyaan mengenai Syarat ini dapat dikirimkan ke
                            <a
                                href="mailto:admin@yourdomain.com"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors"
                                >admin@yourdomain.com</a
                            >.
                        </p>

                        <p class="mb-0 leading-relaxed">
                            <strong>Klausul Bahasa:</strong> Syarat ini tersedia dalam Bahasa
                            Inggris dan Bahasa Indonesia. Keduanya dimaksudkan memiliki makna hukum
                            yang setara. Untuk interpretasi dan penegakan menurut hukum Indonesia,
                            versi Bahasa Indonesia berlaku sepanjang diwajibkan oleh peraturan
                            perundang-undangan.
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-slate-900 text-slate-300 py-12 border-t border-slate-800">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-4 gap-8 mb-8">
                    <div class="col-span-1 md:col-span-2">
                        <span class="text-2xl font-bold text-white tracking-tight mb-4 block">
                            {{ appName }}.
                        </span>
                        <p class="text-slate-400 mb-6 max-w-sm">{{ labels.trustedPartner }}</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">{{ labels.company }}</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <a
                                    :href="localizedPath('/')"
                                    class="hover:text-cyan-400 transition-colors"
                                    >{{ labels.home }}</a
                                >
                            </li>
                            <li>
                                <a
                                    :href="localizedPath('/#solutions')"
                                    class="hover:text-cyan-400 transition-colors"
                                    >{{ labels.solutions }}</a
                                >
                            </li>
                            <li>
                                <a
                                    :href="localizedPath('/#contact')"
                                    class="hover:text-cyan-400 transition-colors"
                                    >{{ labels.contact }}</a
                                >
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">{{ labels.legal }}</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <a
                                    :href="localizedPath('/privacy-policy')"
                                    class="hover:text-cyan-400 transition-colors"
                                    >{{ labels.privacyPolicyMain }}</a
                                >
                            </li>
                            <li>
                                <a
                                    :href="localizedPath('/terms-of-service')"
                                    class="hover:text-cyan-400 transition-colors"
                                    >{{ labels.termsOfServiceMain }}</a
                                >
                            </li>
                            <li>
                                <a
                                    :href="localizedPath('/privacy-policy-waagent')"
                                    class="hover:text-cyan-400 transition-colors"
                                    >{{ labels.privacyPolicy }}</a
                                >
                            </li>
                            <li>
                                <a
                                    :href="localizedPath('/terms-of-service-waagent')"
                                    class="text-cyan-400 cursor-default"
                                    >{{ labels.termsOfService }}</a
                                >
                            </li>
                            <li>
                                <a
                                    :href="localizedPath('/#ai-ethics')"
                                    class="hover:text-cyan-400 transition-colors"
                                    >{{ labels.aiEthics }}</a
                                >
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-slate-800 pt-8 pb-4">
                    <div class="flex justify-center mb-5">
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-cyan-700/40 bg-cyan-900/30 px-4 py-1.5 text-xs font-medium text-cyan-300"
                        >
                            <UIcon name="i-heroicons-shield-check" class="text-sm" />
                            {{ labels.aiCompliance }}
                        </span>
                    </div>
                    <div
                        class="flex flex-col md:flex-row justify-between items-center text-sm text-slate-500"
                    >
                        <p>
                            &copy; {{ new Date().getFullYear() }} {{ appName }}
                            {{ labels.rightsReserved }}
                        </p>
                    </div>
                </div>
            </div>
        </footer>

        <UButton
            v-if="showScrollTop"
            class="fixed bottom-6 right-6 p-4 rounded-full shadow-lg z-50 transition-all duration-300"
            color="cyan"
            icon="i-heroicons-arrow-up"
            @click="scrollToTop"
        />
    </div>
</template>
