<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useHead } from '@unhead/vue';
import mainLogo from '../../images/Main Logo.webp';
import { useMainStore } from '../AppState';

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
            ? `Terms of Service | ${name}`
            : `Syarat dan Ketentuan Layanan | ${name}`;
        const description = isEnglish.value
            ? `Read the Terms of Service for ${name} — covering usage rights, intellectual property, refund policy, and governing law.`
            : `Baca Syarat dan Ketentuan Layanan ${name} mencakup hak penggunaan, kekayaan intelektual, dan hukum yang berlaku.`;
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
    if (isEnglish.value) {
        return {
            backHome: 'Back to Home',
            terms: 'Terms of Service',
            company: 'Company',
            legal: 'Legal',
            home: 'Home',
            solutions: 'Solutions',
            contact: 'Contact',
            privacyPolicy: 'Privacy Policy',
            termsOfService: 'Terms of Service',
            aiEthics: 'AI Ethics',
            trustedPartner:
                'Your trusted partner for enterprise software innovation and digital transformation.',
            rightsReserved: 'All rights reserved.',
            lastUpdated: 'Last Updated: March 5, 2026',
        };
    }

    return {
        backHome: 'Kembali ke Beranda',
        terms: 'Syarat dan Ketentuan Layanan',
        company: 'Perusahaan',
        legal: 'Legal',
        home: 'Beranda',
        solutions: 'Solusi',
        contact: 'Kontak',
        privacyPolicy: 'Kebijakan Privasi',
        termsOfService: 'Syarat dan Ketentuan Layanan',
        aiEthics: 'Etika AI',
        trustedPartner:
            'Mitra tepercaya Anda untuk inovasi perangkat lunak enterprise dan transformasi digital.',
        rightsReserved: 'Seluruh hak dilindungi.',
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
                            Welcome to {{ appName }}. These Terms of Service ("Terms") govern your
                            access to and use of our website, software products, applications, and
                            related services (collectively, the "Service"). By using the Service,
                            you agree to be bound by these Terms.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">1. Definitions</h3>
                        <p class="mb-6 leading-relaxed">
                            "{{ appName }}", "we," and "us" refer to the provider of the Service.
                            "User" and "you" refer to any individual or legal entity using the
                            Service. "Application" means software products provided through the
                            Service.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            2. License to Use
                        </h3>
                        <p class="mb-4 leading-relaxed">
                            We grant a limited, non-exclusive, non-transferable, and revocable
                            license to use the Service in accordance with these Terms.
                        </p>
                        <p class="mb-4 leading-relaxed">You are prohibited from:</p>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li>
                                Copying, modifying, reverse-engineering, or decompiling the Service.
                            </li>
                            <li>
                                Creating derivative works from proprietary parts of the Service.
                            </li>
                            <li>Allowing unauthorized third-party use of the Service.</li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            3. Intellectual Property and Ownership
                        </h3>
                        <p class="mb-4 leading-relaxed">
                            All software, source code, workflows, designs, marks, and related
                            materials in the Service are protected by applicable Indonesian
                            intellectual property laws.
                        </p>
                        <ul class="list-disc pl-6 mb-6 space-y-3 leading-relaxed">
                            <li>
                                <strong>Service IP:</strong> Core platform assets, including
                                AI-assisted outputs used to operate the Service, remain the service
                                provider's property unless otherwise agreed in writing.
                            </li>
                            <li>
                                <strong>User Content:</strong> Data and content provided by users
                                remain owned by users.
                            </li>
                            <li>
                                <strong>Aggregated Data:</strong> We may use anonymized aggregate
                                usage data for service reliability and analytics.
                            </li>
                            <li>
                                <strong>Feedback License:</strong> Feedback submitted by users may
                                be implemented by us under a perpetual, royalty-free license.
                            </li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            4. Payment and Fees
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Prices are shown in applicable currency and may change. Payments are
                            processed through authorized payment channels. You must provide valid
                            and accurate payment information.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">5. Refund Policy</h3>
                        <p class="mb-4 leading-relaxed">
                            Digital products are generally non-refundable once delivered or made
                            accessible, except where mandatory law requires otherwise.
                        </p>
                        <p class="mb-4 leading-relaxed">Refund requests may be considered if:</p>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li>
                                A material technical defect is proven and unresolved within a
                                reasonable remediation period.
                            </li>
                            <li>A verified duplicate transaction occurs.</li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            6. Privacy Policy
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Personal data processing is governed by our Privacy Policy at
                            <a
                                href="/privacy-policy"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors"
                                >/privacy-policy</a
                            >.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            7. Termination of Service
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            We may suspend or terminate access where these Terms are materially
                            violated or where required for security, legal compliance, or platform
                            integrity.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            8. Limitation of Liability
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            The Service is provided on an "as is" and "as available" basis to the
                            fullest extent permitted by law. Liability is limited to direct losses
                            where required by applicable law and excludes indirect or consequential
                            losses to the extent legally permitted.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            9. Changes to Terms
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            We may amend these Terms from time to time. Continued use after updates
                            constitutes acceptance of the revised Terms.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            10. Governing Law
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            These Terms are governed by the laws of the Republic of Indonesia.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            11. AI Disclosure and Ethical Use
                        </h3>
                        <p class="mb-4 leading-relaxed">
                            We use AI-assisted workflows with mandatory Human-in-the-Loop review and
                            professional accountability for final outputs, aligned with SE
                            Menkominfo No. 9 Tahun 2023.
                        </p>
                        <ul class="list-disc pl-6 mb-6 space-y-3 leading-relaxed">
                            <li>
                                AI is used as assistive tooling, not a replacement for human
                                judgment.
                            </li>
                            <li>
                                Client confidential data is not used to train public third-party AI
                                models.
                            </li>
                            <li>
                                Ethical principles include transparency, accountability, fairness,
                                and human-centricity.
                            </li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">12. Contact</h3>
                        <p class="mb-6 leading-relaxed">
                            Questions about these Terms can be sent to
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
                            Selamat datang di {{ appName }}. Syarat dan Ketentuan Layanan ini
                            ("Syarat") mengatur akses dan penggunaan Anda terhadap situs web, produk
                            perangkat lunak, aplikasi, dan layanan terkait kami (secara bersama-sama
                            disebut "Layanan"). Dengan menggunakan Layanan, Anda setuju untuk
                            terikat pada Syarat ini.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">1. Definisi</h3>
                        <p class="mb-6 leading-relaxed">
                            "{{ appName }}" atau "kami" merujuk kepada penyedia Layanan. "Pengguna"
                            atau "Anda" merujuk kepada setiap orang atau badan hukum yang
                            menggunakan Layanan. "Aplikasi" berarti produk perangkat lunak yang
                            disediakan melalui Layanan.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            2. Lisensi Penggunaan
                        </h3>
                        <p class="mb-4 leading-relaxed">
                            Kami memberikan lisensi terbatas, non-eksklusif, tidak dapat dialihkan,
                            dan dapat dicabut untuk menggunakan Layanan sesuai Syarat ini.
                        </p>
                        <p class="mb-4 leading-relaxed">Anda dilarang untuk:</p>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li>
                                Menyalin, memodifikasi, reverse-engineer, atau dekompilasi Layanan.
                            </li>
                            <li>Membuat karya turunan dari bagian proprietary Layanan.</li>
                            <li>Mengizinkan penggunaan pihak ketiga tanpa kewenangan.</li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            3. Hak Kekayaan Intelektual dan Kepemilikan
                        </h3>
                        <p class="mb-4 leading-relaxed">
                            Seluruh perangkat lunak, kode sumber, alur kerja, desain, merek, dan
                            materi terkait dalam Layanan dilindungi oleh peraturan kekayaan
                            intelektual yang berlaku di Indonesia.
                        </p>
                        <ul class="list-disc pl-6 mb-6 space-y-3 leading-relaxed">
                            <li>
                                <strong>HKI Layanan:</strong> Aset inti platform, termasuk output
                                berbantuan AI yang digunakan untuk mengoperasikan Layanan, tetap
                                menjadi milik penyedia Layanan kecuali diperjanjikan lain secara
                                tertulis.
                            </li>
                            <li>
                                <strong>Konten Pengguna:</strong> Data dan konten yang diberikan
                                pengguna tetap menjadi milik pengguna.
                            </li>
                            <li>
                                <strong>Data Agregat:</strong> Kami dapat menggunakan data
                                penggunaan agregat anonim untuk keandalan layanan dan analitik.
                            </li>
                            <li>
                                <strong>Lisensi Umpan Balik:</strong> Umpan balik dari pengguna
                                dapat kami implementasikan berdasarkan lisensi yang bersifat
                                permanen dan bebas royalti.
                            </li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            4. Pembayaran dan Biaya
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Harga ditampilkan dalam mata uang yang berlaku dan dapat berubah.
                            Pembayaran diproses melalui kanal pembayaran resmi. Anda wajib
                            memberikan informasi pembayaran yang valid dan akurat.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            5. Kebijakan Pengembalian Dana
                        </h3>
                        <p class="mb-4 leading-relaxed">
                            Produk digital pada umumnya tidak dapat dikembalikan setelah diserahkan
                            atau dapat diakses, kecuali diwajibkan lain oleh hukum yang berlaku.
                        </p>
                        <p class="mb-4 leading-relaxed">
                            Permintaan pengembalian dana dapat dipertimbangkan apabila:
                        </p>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li>
                                Terbukti terdapat cacat teknis material yang tidak terselesaikan
                                dalam jangka waktu perbaikan yang wajar.
                            </li>
                            <li>Terjadi transaksi ganda yang terverifikasi.</li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            6. Kebijakan Privasi
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Pemrosesan data pribadi diatur dalam Kebijakan Privasi kami di
                            <a
                                href="/privacy-policy"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors"
                                >/privacy-policy</a
                            >.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            7. Penghentian Layanan
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Kami dapat menangguhkan atau menghentikan akses apabila terjadi
                            pelanggaran material terhadap Syarat ini, atau untuk kepentingan
                            keamanan, kepatuhan hukum, maupun integritas platform.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            8. Batasan Tanggung Jawab
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Layanan disediakan dalam kondisi "sebagaimana adanya" dan "sebagaimana
                            tersedia" sepanjang diperbolehkan hukum. Tanggung jawab dibatasi pada
                            kerugian langsung apabila diwajibkan hukum yang berlaku dan
                            mengecualikan kerugian tidak langsung atau konsekuensial sepanjang
                            diperbolehkan hukum.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            9. Perubahan Syarat
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Kami dapat mengubah Syarat ini dari waktu ke waktu. Penggunaan Layanan
                            secara berkelanjutan setelah pembaruan berarti Anda menyetujui Syarat
                            yang telah diperbarui.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            10. Hukum yang Berlaku
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Syarat ini tunduk dan ditafsirkan berdasarkan hukum Republik Indonesia.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            11. Pengungkapan AI dan Penggunaan Etis
                        </h3>
                        <p class="mb-4 leading-relaxed">
                            Kami menggunakan alur kerja berbantuan AI dengan proses wajib
                            Human-in-the-Loop dan tanggung jawab profesional atas hasil akhir,
                            selaras dengan prinsip tata kelola AI yang etis.
                        </p>
                        <ul class="list-disc pl-6 mb-6 space-y-3 leading-relaxed">
                            <li>
                                AI digunakan sebagai alat bantu, bukan pengganti penilaian manusia.
                            </li>
                            <li>
                                Data rahasia klien tidak digunakan untuk melatih model AI publik
                                pihak ketiga.
                            </li>
                            <li>
                                Prinsip etika yang diterapkan meliputi transparansi, akuntabilitas,
                                keadilan, dan berpusat pada manusia.
                            </li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">12. Kontak</h3>
                        <p class="mb-6 leading-relaxed">
                            Pertanyaan terkait Syarat ini dapat dikirim ke
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
                            {{ appName }}<span class="text-cyan-600">.</span>
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
                                    >{{ labels.privacyPolicy }}</a
                                >
                            </li>
                            <li>
                                <a
                                    :href="localizedPath('/terms-of-service')"
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
                    <div
                        class="flex flex-col md:flex-row justify-between items-center text-sm text-slate-500"
                    >
                        <p>
                            &copy; {{ new Date().getFullYear() }} {{ appName }}.
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
