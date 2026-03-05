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
        const title = isEnglish.value ? `Privacy Policy | ${name}` : `Kebijakan Privasi | ${name}`;
        const description = isEnglish.value
            ? `Privacy Policy by ${name} describes data processing, messaging platform integration, AI transparency, and your rights.`
            : `Kebijakan Privasi ${name} menjelaskan pemrosesan data, integrasi platform pesan, transparansi AI, dan hak Anda.`;

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
            privacyPolicyMain: 'Privacy Policy',
            termsOfServiceMain: 'Terms of Service',
            privacyPolicy: 'Privacy Policy (App)',
            termsOfService: 'Terms of Service (App)',
            aiEthics: 'AI Ethics',
            company: 'Company',
            legal: 'Legal',
            home: 'Home',
            solutions: 'Solutions',
            contact: 'Contact',
            trustedPartner:
                'Your trusted partner for enterprise software innovation and digital transformation.',
            rightsReserved: 'All rights reserved.',
            aiCompliance: 'AI-Assisted & Human-Reviewed',
            lastUpdated: 'Last Updated: March 5, 2026',
            title: `Privacy Policy for App by ${name}`,
        };
    }

    return {
        backHome: 'Kembali ke Beranda',
        privacyPolicyMain: 'Kebijakan Privasi',
        termsOfServiceMain: 'Syarat dan Ketentuan Layanan',
        privacyPolicy: 'Kebijakan Privasi (App)',
        termsOfService: 'Syarat dan Ketentuan (App)',
        aiEthics: 'Etika AI',
        company: 'Perusahaan',
        legal: 'Legal',
        home: 'Beranda',
        solutions: 'Solusi',
        contact: 'Kontak',
        trustedPartner:
            'Mitra tepercaya Anda untuk inovasi perangkat lunak enterprise dan transformasi digital.',
        rightsReserved: 'Seluruh hak dilindungi.',
        aiCompliance: 'Dibantu AI & Ditinjau Manusia',
        lastUpdated: 'Terakhir Diperbarui: 5 Maret 2026',
        title: `Kebijakan Privasi App oleh ${name}`,
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
        class="privacy-policy min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-cyan-100 selection:text-cyan-900"
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
                        {{ labels.title }}
                    </h1>
                    <p class="text-slate-500 text-center mb-12">{{ labels.lastUpdated }}</p>

                    <div v-if="isEnglish" class="prose prose-slate max-w-none text-slate-600">
                        <p class="mb-6 leading-relaxed">
                            This Privacy Policy describes how {{ appName }} ("we," "us," or "our")
                            processes personal data when you use the Application on the WhatsApp
                            Business ecosystem (the "Service"). We process personal data in
                            accordance with Indonesia Law No. 27 of 2022 concerning Personal Data
                            Protection ("UU PDP") and other applicable regulations.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            1. Personal Data We Collect and Why
                        </h3>
                        <p class="mb-4 leading-relaxed">
                            <strong>a. Data You Provide Directly:</strong>
                        </p>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li><strong>Identity Data:</strong> Name and account identifiers.</li>
                            <li><strong>Contact Data:</strong> Email address and phone number.</li>
                            <li>
                                <strong>Account Data:</strong> Login credentials and profile
                                settings.
                            </li>
                            <li>
                                <strong>Billing Data:</strong> Payment-related records where
                                applicable.
                            </li>
                        </ul>
                        <p class="mb-4 leading-relaxed">
                            <strong>b. Data Processed via WhatsApp/Meta Platform:</strong> Account
                            registration data, service activity, connection metadata, and message
                            interaction context as required to deliver WhatsApp-integrated features.
                        </p>
                        <p class="mb-6 leading-relaxed">
                            <strong>Legal Basis:</strong> Consent, contractual necessity, legitimate
                            interests, and legal obligations depending on processing purpose.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            2. How We Use Your Personal Data
                        </h3>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li>To provide, maintain, and secure WaAgent services.</li>
                            <li>To create and manage user accounts and service access.</li>
                            <li>To process transactions and handle operational communications.</li>
                            <li>To improve reliability, features, and support quality.</li>
                            <li>To comply with legal and regulatory requirements.</li>
                        </ul>
                        <p class="mb-4 leading-relaxed">
                            <strong>Automated Processing:</strong> AI-assisted processing may be
                            used for operational workflows. Where processing significantly affects
                            your legal rights or interests, you may request human intervention,
                            express your view, and contest outcomes.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            3. Data Sharing and Disclosure
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            We do not sell or rent personal data. We disclose data only when
                            necessary, including to platform partners such as Meta/WhatsApp, trusted
                            processors under written obligations, and lawful authorities where
                            required by law.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            4. Your Rights as a Data Subject
                        </h3>
                        <p class="mb-6 leading-relaxed">Under UU PDP, you have rights to:</p>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li>Access and obtain a copy of your personal data.</li>
                            <li>Rectify inaccurate and/or incomplete data.</li>
                            <li>Request deletion where legally applicable.</li>
                            <li>Withdraw consent where processing relies on consent.</li>
                            <li>Object to or restrict certain processing activities.</li>
                            <li>Request portability where legally applicable.</li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">5. Data Security</h3>
                        <p class="mb-6 leading-relaxed">
                            We implement appropriate technical and organizational safeguards,
                            including access controls, monitoring, and encryption where relevant.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            6. Data Breach Notification
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            If a personal data breach occurs, we notify relevant authorities and
                            affected data subjects no later than 3 x 24 hours after becoming aware,
                            where required by law.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            7. Data Retention
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            We retain personal data only for as long as necessary for lawful
                            business purposes, contractual requirements, and legal obligations.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            8. International Data Transfers
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            International transfer, if any, is conducted in compliance with UU PDP
                            and applicable implementing regulations.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            9. Changes to This Policy
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            We may update this Privacy Policy from time to time. The effective date
                            is shown in the Last Updated section.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            10. Data Protection Officer
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            To exercise your rights or submit privacy requests, contact our
                            <strong>Data Protection Officer (DPO)</strong>:<br />
                            Email:
                            <a
                                href="mailto:admin@yourdomain.com"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors"
                                >admin@yourdomain.com</a
                            ><br />
                            Attn: Privacy Compliance Team<br />
                            <span class="text-slate-500 text-sm"
                                >Target response time: 14 business days.</span
                            >
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            11. AI Transparency and Ethical Use
                        </h3>
                        <ul class="list-disc pl-6 mb-6 space-y-3 leading-relaxed">
                            <li>
                                <strong>AI in Operations:</strong> AI-assisted workflows are used
                                for operational efficiency with Human-in-the-Loop review.
                            </li>
                            <li>
                                <strong>No Unauthorized Model Training:</strong> Personal data is
                                not used to train public third-party models without explicit legal
                                basis and safeguards.
                            </li>
                            <li>
                                <strong>Ethical Principles:</strong> Transparency, accountability,
                                fairness, and human-centricity are maintained.
                            </li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">12. Contact Us</h3>
                        <p class="mb-6 leading-relaxed">
                            <strong>{{ appName }}</strong
                            ><br />
                            General Inquiries:
                            <a
                                href="mailto:admin@yourdomain.com"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors"
                                >admin@yourdomain.com</a
                            ><br />
                            Privacy and Data Protection:
                            <a
                                href="mailto:admin@yourdomain.com"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors"
                                >admin@yourdomain.com</a
                            >
                        </p>

                        <p class="mb-0 leading-relaxed">
                            <strong>Language Clause:</strong> This Privacy Policy is provided in
                            English and Indonesian. Both versions are intended to have the same
                            legal effect. For processing and enforcement under Indonesian law, the
                            Indonesian version prevails to the extent required by applicable law.
                        </p>
                    </div>

                    <div v-else class="prose prose-slate max-w-none text-slate-600">
                        <p class="mb-6 leading-relaxed">
                            Kebijakan Privasi ini menjelaskan bagaimana {{ appName }}
                            ("kami") memproses data pribadi saat Anda menggunakan Aplikasi pada
                            ekosistem WhatsApp Business ("Layanan"). Pemrosesan data pribadi
                            dilakukan sesuai Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan
                            Data Pribadi ("UU PDP") dan peraturan lain yang berlaku.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            1. Data Pribadi yang Kami Kumpulkan dan Tujuannya
                        </h3>
                        <p class="mb-4 leading-relaxed">
                            <strong>a. Data yang Anda Berikan:</strong>
                        </p>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li><strong>Data Identitas:</strong> Nama dan identitas akun.</li>
                            <li><strong>Data Kontak:</strong> Email dan nomor telepon.</li>
                            <li>
                                <strong>Data Akun:</strong> Kredensial login dan pengaturan profil.
                            </li>
                            <li>
                                <strong>Data Tagihan:</strong> Data terkait pembayaran jika berlaku.
                            </li>
                        </ul>
                        <p class="mb-4 leading-relaxed">
                            <strong>b. Data melalui Platform WhatsApp/Meta:</strong> Data
                            pendaftaran akun, aktivitas layanan, metadata koneksi, serta konteks
                            interaksi pesan yang diperlukan untuk fitur integrasi WhatsApp.
                        </p>
                        <p class="mb-6 leading-relaxed">
                            <strong>Dasar Hukum:</strong> Persetujuan, kebutuhan kontraktual,
                            kepentingan yang sah, dan kewajiban hukum sesuai tujuan pemrosesan.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            2. Cara Kami Menggunakan Data Pribadi Anda
                        </h3>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li>Menyediakan, memelihara, dan mengamankan layanan WaAgent.</li>
                            <li>Membuat dan mengelola akun pengguna serta akses layanan.</li>
                            <li>Memproses transaksi dan komunikasi operasional.</li>
                            <li>Meningkatkan keandalan, fitur, dan kualitas dukungan.</li>
                            <li>Memenuhi kewajiban hukum dan regulasi.</li>
                        </ul>
                        <p class="mb-4 leading-relaxed">
                            <strong>Pemrosesan Otomatis:</strong> Pemrosesan berbantuan AI dapat
                            digunakan untuk alur operasional. Jika berdampak signifikan pada hak
                            atau kepentingan hukum Anda, Anda berhak meminta intervensi manusia,
                            menyampaikan pendapat, dan mengajukan keberatan.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            3. Berbagi dan Pengungkapan Data
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Kami tidak menjual atau menyewakan data pribadi. Pengungkapan hanya
                            dilakukan jika diperlukan, termasuk kepada mitra platform seperti
                            Meta/WhatsApp, pemroses tepercaya dengan kewajiban tertulis, dan
                            otoritas yang berwenang sesuai hukum.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            4. Hak Anda sebagai Subjek Data
                        </h3>
                        <p class="mb-6 leading-relaxed">Berdasarkan UU PDP, Anda berhak untuk:</p>
                        <ul class="list-disc pl-6 mb-6 space-y-2 leading-relaxed">
                            <li>Mengakses dan memperoleh salinan data pribadi Anda.</li>
                            <li>Memperbaiki data yang tidak akurat dan/atau tidak lengkap.</li>
                            <li>Meminta penghapusan sesuai ketentuan hukum yang berlaku.</li>
                            <li>Mencabut persetujuan jika pemrosesan berbasis persetujuan.</li>
                            <li>Membatasi atau menolak pemrosesan tertentu.</li>
                            <li>Meminta portabilitas data sesuai ketentuan yang berlaku.</li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">5. Keamanan Data</h3>
                        <p class="mb-6 leading-relaxed">
                            Kami menerapkan langkah teknis dan organisasi yang sesuai, termasuk
                            kontrol akses, pemantauan, dan enkripsi jika relevan.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            6. Pemberitahuan Pelanggaran Data
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Apabila terjadi pelanggaran data pribadi, kami memberitahukan otoritas
                            terkait dan subjek data terdampak paling lambat 3 x 24 jam sejak
                            diketahui, sesuai ketentuan hukum.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">7. Retensi Data</h3>
                        <p class="mb-6 leading-relaxed">
                            Kami menyimpan data pribadi hanya selama diperlukan untuk tujuan yang
                            sah, kebutuhan kontraktual, dan kewajiban hukum.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            8. Transfer Data Internasional
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Transfer lintas negara, jika ada, dilakukan sesuai UU PDP dan peraturan
                            pelaksana yang berlaku.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            9. Perubahan Kebijakan Ini
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu.
                            Tanggal efektif tercantum pada bagian Terakhir Diperbarui.
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            10. Pejabat Pelindungan Data Pribadi
                        </h3>
                        <p class="mb-6 leading-relaxed">
                            Untuk menggunakan hak Anda atau menyampaikan permintaan privasi, hubungi
                            <strong
                                >Data Protection Officer (DPO) / Pejabat Pelindungan Data
                                Pribadi</strong
                            >:<br />
                            Email:
                            <a
                                href="mailto:admin@yourdomain.com"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors"
                                >admin@yourdomain.com</a
                            ><br />
                            Attn: Privacy Compliance Team<br />
                            <span class="text-slate-500 text-sm"
                                >Target waktu tanggapan: 14 hari kerja.</span
                            >
                        </p>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">
                            11. Transparansi AI dan Penggunaan Etis
                        </h3>
                        <ul class="list-disc pl-6 mb-6 space-y-3 leading-relaxed">
                            <li>
                                <strong>AI dalam Operasional:</strong> Alur kerja berbantuan AI
                                digunakan untuk efisiensi operasional dengan proses
                                Human-in-the-Loop.
                            </li>
                            <li>
                                <strong>Tidak Ada Pelatihan Tanpa Dasar Hukum:</strong> Data pribadi
                                tidak digunakan untuk melatih model AI publik pihak ketiga tanpa
                                dasar hukum eksplisit dan perlindungan yang memadai.
                            </li>
                            <li>
                                <strong>Prinsip Etika:</strong> Transparansi, akuntabilitas,
                                keadilan, dan berpusat pada manusia diterapkan secara konsisten.
                            </li>
                        </ul>

                        <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4">12. Hubungi Kami</h3>
                        <p class="mb-6 leading-relaxed">
                            <strong>{{ appName }}</strong
                            ><br />
                            Pertanyaan Umum:
                            <a
                                href="mailto:admin@yourdomain.com"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors"
                                >admin@yourdomain.com</a
                            ><br />
                            Privasi dan Pelindungan Data:
                            <a
                                href="mailto:admin@yourdomain.com"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors"
                                >admin@yourdomain.com</a
                            >
                        </p>

                        <p class="mb-0 leading-relaxed">
                            <strong>Klausul Bahasa:</strong> Kebijakan Privasi ini tersedia dalam
                            Bahasa Inggris dan Bahasa Indonesia. Keduanya dimaksudkan memiliki
                            kekuatan hukum yang sama. Untuk pemrosesan dan penegakan berdasarkan
                            hukum Indonesia, versi Bahasa Indonesia berlaku sepanjang diwajibkan
                            oleh peraturan perundang-undangan.
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
                                    class="text-cyan-400 cursor-default"
                                    >{{ labels.privacyPolicy }}</a
                                >
                            </li>
                            <li>
                                <a
                                    :href="localizedPath('/terms-of-service-waagent')"
                                    class="hover:text-cyan-400 transition-colors"
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
