<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useHead } from '@unhead/vue';
import mainLogo from '../../images/Main Logo.webp';
import { animate, stagger } from 'animejs';
import { useMainStore } from '../AppState';

type Locale = 'en' | 'id';

const main = useMainStore();
const { appName } = storeToRefs(main);

const mobileMenuOpen = ref(false);
const showScrollTop = ref(false);
const language = ref<Locale>('en');

const words = {
    en: ['Automate', 'Scale', 'Stabilize', 'Orchestrate', 'Optimize'],
    id: ['Otomatiskan', 'Skalakan', 'Stabilkan', 'Orkestrasi', 'Optimalkan'],
};
const currentWord = ref(words.en[0]);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const setLanguage = (locale: Locale) => {
    language.value = locale;
    currentWord.value = words[locale][0];
    if (typeof window !== 'undefined') {
        window.localStorage.setItem('site_language', locale);
        const url = new URL(window.location.href);
        url.searchParams.set('lang', locale);
        window.history.replaceState({}, '', url.toString());
    }
};

const localizedPath = (path: string): string => `${path}?lang=${language.value}`;

const navItems = computed(() =>
    language.value === 'en'
        ? [
              { id: 'solutions', label: 'Solutions' },
              { id: 'services', label: 'Services' },
              { id: 'process', label: 'Process' },
              { id: 'contact', label: 'Contact' },
          ]
        : [
              { id: 'solutions', label: 'Solusi' },
              { id: 'services', label: 'Layanan' },
              { id: 'process', label: 'Proses' },
              { id: 'contact', label: 'Kontak' },
          ],
);

const copy = computed(() => {
    if (language.value === 'en') {
        return {
            partnerBadge: 'Enterprise Software Partner',
            heroTitlePrefix: 'Build Applications That',
            heroTitleSuffix: 'Your Business',
            heroDescription:
                'We engineer high-availability systems with complex business logic, built for scalability and real-world traffic. Robust platforms that reduce manual workload, protect uptime, and grow with your team.',
            ctaPrimary: 'Get in Touch',
            ctaSecondary: 'Explore Solutions',
            consultation: 'Get in Touch',
            trust: [
                { value: '99.9%', label: 'Target Uptime SLA' },
                { value: '10x', label: 'Faster Delivery with AI-Augmented Workflows' },
                { value: '∞', label: 'Horizontally Scalable Architecture' },
                { value: '5★', label: 'Code Quality and Security Standards' },
            ],
            builtFor: 'Built for Security, Speed, and Scalability',
            stackDesc:
                'We choose Laravel and Vue for secure, maintainable product foundations, and Python for integrations and data workflows.',
            solutionsTitle: 'Platform Capabilities',
            solutionsDesc:
                'Engineering experience across high-volume operations, financial-grade reconciliation, and secure data environments.',
            solutions: [
                {
                    title: 'Scalable SaaS Platform',
                    desc: 'Multi-tenant architecture supporting thousands of concurrent users with automated workflows, reliable delivery, and stability under load.',
                },
                {
                    title: 'Enterprise Operations Dashboard',
                    desc: 'Real-time monitoring and management platform with data synchronization, analytics, and reconciliation-grade accuracy.',
                },
                {
                    title: 'Secure Data Management System',
                    desc: 'Security-first architecture with strict access controls, audit trails, and complex business logic for regulated environments.',
                },
            ],
            servicesTitle: 'Our Expertise',
            servicesDesc:
                'End-to-end development capabilities to bring your digital vision to life.',
            services: [
                {
                    title: 'Web App Development',
                    icon: 'i-heroicons-code-bracket',
                    desc: 'Custom web applications built on modern frameworks with high performance and scalability.',
                    points: ['SaaS Platforms', 'Internal Dashboards'],
                },
                {
                    title: 'Mobile Solutions',
                    icon: 'i-heroicons-device-phone-mobile',
                    desc: 'Native and cross-platform mobile applications with production-grade delivery quality.',
                    points: ['React Native / Flutter', 'PWA Development'],
                },
                {
                    title: 'Messaging Automation',
                    icon: 'i-heroicons-chat-bubble-left-right',
                    desc: 'AI-driven chatbot and notification automation workflows integrated with popular messaging platforms.',
                    points: ['AI Virtual Agents', 'Customer Support Automation'],
                },
                {
                    title: 'WordPress Solutions',
                    icon: 'i-logos-wordpress-icon',
                    desc: 'Custom WordPress installations and bespoke themes built for performance, SEO, and ease of content management — from landing pages to full business sites.',
                    points: ['Custom Themes & Templates', 'WooCommerce & Plugin Integration'],
                },
            ],
            processTitle: 'Our Workflow',
            processDesc: 'Transparent, agile, and results-driven.',
            processSteps: [
                { title: 'Discovery', desc: 'Requirement analysis and strategic planning.' },
                { title: 'Design', desc: 'UI/UX prototyping and architecture design.' },
                { title: 'Development', desc: 'Agile sprints and quality assurance testing.' },
                { title: 'Deployment', desc: 'Launch, monitoring, and scale-up.' },
            ],
            aiBadge: 'Transparent by Design',
            aiTitle: 'AI-Assisted. Human-Accountable.',
            aiDesc: 'We leverage state-of-the-art Agentic AI workflows to accelerate delivery and stay transparent about their boundaries.',
            aiItems: [
                {
                    title: 'AI-Augmented Engineering',
                    desc: 'AI accelerates prototyping, code generation, and design quality as a force multiplier for engineers.',
                },
                {
                    title: 'Human-in-the-Loop (HITL)',
                    desc: 'Every AI-assisted output is reviewed by a qualified engineer before release.',
                },
                {
                    title: 'Your Data Stays Yours',
                    desc: 'We do not use proprietary client or user data to train public third-party AI models.',
                },
                {
                    title: 'IP Clarity & Ownership',
                    desc: 'Ownership terms are clear for platform IP and custom deliverables.',
                },
                {
                    title: 'Ethical AI Governance',
                    desc: 'Our AI governance follows ethical principles of transparency, accountability, and fairness.',
                },
                {
                    title: 'Best Effort & Accountability',
                    desc: 'We commit to prompt remediation and professional responsibility for all final outputs.',
                },
            ],
            aiFoot: 'For full details, including AI disclosure and limitation of liability, see our',
            footerTagline:
                'Your trusted partner for enterprise software innovation and digital transformation.',
            company: 'Company',
            legal: 'Legal',
            home: 'About Us',
            contact: 'Contact',
            privacy: 'Privacy Policy',
            terms: 'Terms of Service',
            aiEthics: 'AI Ethics',
            rightsReserved: 'All rights reserved.',
            aiCompliance: 'AI-Assisted & Human-Reviewed',
        };
    }

    return {
        partnerBadge: 'Mitra Perangkat Lunak Enterprise',
        heroTitlePrefix: 'Bangun Aplikasi yang',
        heroTitleSuffix: 'Bisnis Anda',
        heroDescription:
            'Kami merekayasa sistem high-availability dengan logika bisnis kompleks, dirancang untuk skalabilitas dan trafik nyata. Platform tangguh yang mengurangi beban manual, menjaga uptime, dan tumbuh bersama tim Anda.',
        ctaPrimary: 'Hubungi Kami',
        ctaSecondary: 'Lihat Solusi',
        consultation: 'Hubungi Kami',
        trust: [
            { value: '99.9%', label: 'Target SLA Uptime' },
            { value: '10x', label: 'Delivery Lebih Cepat dengan AI-Augmented Workflow' },
            { value: '∞', label: 'Arsitektur Horizontal Scalable' },
            { value: '5★', label: 'Standar Kualitas Kode dan Keamanan' },
        ],
        builtFor: 'Dirancang untuk Keamanan, Kecepatan, dan Skalabilitas',
        stackDesc:
            'Kami memilih Laravel dan Vue untuk fondasi produk yang aman dan terawat, serta Python untuk integrasi dan alur data.',
        solutionsTitle: 'Kapabilitas Platform',
        solutionsDesc:
            'Pengalaman engineering pada operasi volume tinggi, rekonsiliasi berstandar finansial, dan lingkungan data yang aman.',
        solutions: [
            {
                title: 'Platform SaaS Skalabel',
                desc: 'Arsitektur multi-tenant yang mendukung ribuan pengguna bersamaan dengan alur kerja otomatis, pengiriman andal, dan stabil di bawah beban.',
            },
            {
                title: 'Dashboard Operasi Enterprise',
                desc: 'Platform pemantauan dan manajemen real-time dengan sinkronisasi data, analitik, dan akurasi setingkat rekonsiliasi.',
            },
            {
                title: 'Sistem Manajemen Data yang Aman',
                desc: 'Arsitektur berfokus keamanan dengan kontrol akses ketat, jejak audit, dan logika bisnis kompleks untuk lingkungan ter-regulasi.',
            },
        ],
        servicesTitle: 'Keahlian Kami',
        servicesDesc: 'Kapabilitas pengembangan end-to-end untuk mewujudkan visi digital Anda.',
        services: [
            {
                title: 'Pengembangan Aplikasi Web',
                icon: 'i-heroicons-code-bracket',
                desc: 'Aplikasi web kustom berbasis framework modern dengan performa dan skalabilitas tinggi.',
                points: ['Platform SaaS', 'Dashboard Internal'],
            },
            {
                title: 'Solusi Mobile',
                icon: 'i-heroicons-device-phone-mobile',
                desc: 'Aplikasi mobile native dan lintas platform dengan kualitas delivery berstandar produksi.',
                points: ['React Native / Flutter', 'Pengembangan PWA'],
            },
            {
                title: 'Otomasi Pesan',
                icon: 'i-heroicons-chat-bubble-left-right',
                desc: 'Chatbot berbasis AI dan alur notifikasi otomatis yang terintegrasi dengan platform pesan populer.',
                points: ['Agen Virtual AI', 'Otomasi Dukungan Pelanggan'],
            },
            {
                title: 'Solusi WordPress',
                icon: 'i-logos-wordpress-icon',
                desc: 'Instalasi WordPress kustom dan tema bespoke yang dioptimalkan untuk performa, SEO, dan kemudahan pengelolaan konten — dari landing page hingga situs bisnis lengkap.',
                points: ['Tema & Template Kustom', 'Integrasi WooCommerce & Plugin'],
            },
        ],
        processTitle: 'Alur Kerja Kami',
        processDesc: 'Transparan, agile, dan berorientasi hasil.',
        processSteps: [
            { title: 'Discovery', desc: 'Analisis kebutuhan dan perencanaan strategis.' },
            { title: 'Desain', desc: 'Prototyping UI/UX dan perancangan arsitektur.' },
            { title: 'Pengembangan', desc: 'Sprint agile dan pengujian quality assurance.' },
            { title: 'Deployment', desc: 'Peluncuran, pemantauan, dan scale-up.' },
        ],
        aiBadge: 'Transparan sejak Desain',
        aiTitle: 'Dibantu AI. Bertanggung Jawab oleh Manusia.',
        aiDesc: 'Kami memanfaatkan alur Agentic AI terkini untuk mempercepat delivery dan tetap transparan terhadap batas penggunaannya.',
        aiItems: [
            {
                title: 'Engineering Berbantuan AI',
                desc: 'AI mempercepat prototyping, code generation, dan kualitas desain sebagai pengganda kemampuan engineer.',
            },
            {
                title: 'Human-in-the-Loop (HITL)',
                desc: 'Setiap output berbantuan AI ditinjau engineer berkompeten sebelum dirilis.',
            },
            {
                title: 'Data Anda Tetap Milik Anda',
                desc: 'Kami tidak menggunakan data proprietary klien atau pengguna untuk melatih model AI publik pihak ketiga.',
            },
            {
                title: 'Kejelasan HKI & Kepemilikan',
                desc: 'Ketentuan kepemilikan jelas untuk HKI platform maupun deliverable kustom.',
            },
            {
                title: 'Tata Kelola AI Etis',
                desc: 'Tata kelola AI kami menerapkan prinsip transparansi, akuntabilitas, dan keadilan.',
            },
            {
                title: 'Best Effort & Akuntabilitas',
                desc: 'Kami berkomitmen pada remediasi cepat dan tanggung jawab profesional atas seluruh output akhir.',
            },
        ],
        aiFoot: 'Untuk detail lengkap, termasuk pengungkapan AI dan batasan tanggung jawab, lihat',
        footerTagline:
            'Mitra tepercaya Anda untuk inovasi perangkat lunak enterprise dan transformasi digital.',
        company: 'Perusahaan',
        legal: 'Legal',
        home: 'Tentang Kami',
        contact: 'Kontak',
        privacy: 'Kebijakan Privasi',
        terms: 'Syarat dan Ketentuan Layanan',
        aiEthics: 'Etika AI',
        rightsReserved: 'Seluruh hak dilindungi.',
        aiCompliance: 'Dibantu AI & Ditinjau Manusia',
    };
});

useHead(
    computed(() => {
        const en = language.value === 'en';
        const name = appName.value || import.meta.env.VITE_APP_NAME || 'Application';
        const title = en
            ? `${name} | High-Availability Software Solutions`
            : `${name} | Solusi Perangkat Lunak High-Availability`;
        const description = en
            ? `${name} engineers high-availability systems, custom web apps, and mobile solutions to securely orchestrate your business operations.`
            : `${name} merancang sistem high-availability, aplikasi web kustom, dan solusi mobile untuk mengotomatisasi dan mengamankan operasional bisnis Anda.`;
        const locale = en ? 'en_US' : 'id_ID';
        return {
            title,
            meta: [
                { name: 'description', content: description },
                { property: 'og:title', content: title },
                { property: 'og:description', content: description },
                { property: 'og:type', content: 'website' },
                { property: 'og:locale', content: locale },
                { name: 'robots', content: 'index, follow' },
            ],
        };
    }),
);

const scrollToSection = (sectionId: string) => {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
        if (mobileMenuOpen.value) {
            mobileMenuOpen.value = false;
        }
    }
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
    currentWord.value = words[language.value][0];

    window.addEventListener('scroll', () => {
        showScrollTop.value = window.scrollY > 300;
    });

    let wordIndex = 0;
    setInterval(() => {
        const activeWords = words[language.value];
        wordIndex = (wordIndex + 1) % activeWords.length;
        currentWord.value = activeWords[wordIndex];
    }, 2500);

    animate('.hero-dashboard', {
        translateY: [-10, 10],
        rotateX: [6, 4],
        loop: true,
        direction: 'alternate',
        easing: 'easeInOutSine',
        duration: 3000,
    });

    const observer = new IntersectionObserver(
        (entries) => {
            const targets = entries
                .filter((entry) => entry.isIntersecting)
                .map((entry) => entry.target);
            if (targets.length > 0) {
                animate(targets, {
                    opacity: [0, 1],
                    translateY: [50, 0],
                    duration: 1000,
                    easing: 'easeOutExpo',
                    delay: stagger(200),
                });
                targets.forEach((target) => observer.unobserve(target));
            }
        },
        { threshold: 0.1 },
    );

    document.querySelectorAll('.animate-on-scroll').forEach((el) => {
        (el as HTMLElement).style.opacity = '0';
        observer.observe(el);
    });
});
</script>

<template>
    <div
        class="landing-page min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-cyan-100 selection:text-cyan-900"
    >
        <header
            class="bg-white/90 backdrop-blur-md sticky top-0 w-full z-50 border-b border-slate-200"
        >
            <div
                class="container mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center"
            >
                <a
                    href="#home"
                    class="flex items-center gap-2 group"
                    @click.prevent="scrollToSection('home')"
                >
                    <img :src="mainLogo" :alt="appName || 'Logo'" class="h-10 w-auto" />
                    <span
                        class="text-xl font-bold tracking-tight text-slate-900 group-hover:text-cyan-600 transition-colors"
                    >
                        {{ appName }}<span class="text-cyan-600">.</span>
                    </span>
                </a>

                <nav class="hidden md:flex items-center space-x-8">
                    <a
                        v-for="item in navItems"
                        :key="item.id"
                        :href="`#${item.id}`"
                        class="text-sm font-medium text-slate-600 hover:text-cyan-600 transition-colors"
                        @click.prevent="scrollToSection(item.id)"
                    >
                        {{ item.label }}
                    </a>
                </nav>

                <div class="hidden md:flex items-center gap-3">
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
                    <a
                        href="#contact"
                        class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white transition-all bg-slate-900 rounded-lg hover:bg-slate-800"
                        @click.prevent="scrollToSection('contact')"
                    >
                        {{ copy.consultation }}
                    </a>
                </div>

                <div class="md:hidden flex items-center gap-2">
                    <a
                        href="#contact"
                        class="inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-white bg-slate-900 rounded-lg hover:bg-slate-800 whitespace-nowrap"
                        @click.prevent="scrollToSection('contact')"
                    >
                        {{ copy.consultation }}
                    </a>
                    <button
                        type="button"
                        class="md:hidden p-2 text-slate-600 hover:text-slate-900"
                        @click="toggleMobileMenu"
                    >
                        <UIcon name="i-heroicons-bars-3" class="w-6 h-6" />
                    </button>
                </div>
            </div>

            <div
                v-show="mobileMenuOpen"
                class="md:hidden bg-white border-t border-slate-200 shadow-xl absolute w-full"
            >
                <nav class="flex flex-col px-4 py-6 space-y-4">
                    <a
                        v-for="item in navItems"
                        :key="item.id"
                        :href="`#${item.id}`"
                        class="text-base font-medium text-slate-600 hover:text-cyan-600"
                        @click.prevent="scrollToSection(item.id)"
                        >{{ item.label }}</a
                    >
                    <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1">
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
                    <a
                        href="#contact"
                        class="inline-flex justify-center w-full px-5 py-3 text-base font-semibold text-white bg-slate-900 rounded-lg hover:bg-slate-800"
                        @click.prevent="scrollToSection('contact')"
                        >{{ copy.consultation }}</a
                    >
                </nav>
            </div>
        </header>

        <main>
            <section id="home" class="relative pt-24 pb-20 lg:pt-32 lg:pb-28 overflow-hidden">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">
                        <div class="max-w-2xl mx-auto lg:mx-0 text-center lg:text-left">
                            <div
                                class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium text-cyan-700 bg-cyan-50 border border-cyan-100 mb-6"
                            >
                                {{ copy.partnerBadge }}
                            </div>
                            <h1
                                class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-slate-900 mb-6 leading-tight"
                            >
                                {{ copy.heroTitlePrefix }}
                                <span class="relative inline-block align-bottom">
                                    <Transition name="flip" mode="out-in">
                                        <span
                                            :key="currentWord"
                                            class="inline-block text-transparent bg-clip-text bg-gradient-to-r from-cyan-500 to-blue-600 will-change-[opacity,transform]"
                                        >
                                            {{ currentWord }}
                                        </span>
                                    </Transition>
                                </span>
                                {{ copy.heroTitleSuffix }}
                            </h1>
                            <p class="text-lg sm:text-xl text-slate-600 mb-8 leading-relaxed">
                                {{ copy.heroDescription }}
                            </p>
                            <div
                                class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start"
                            >
                                <a
                                    href="#contact"
                                    class="inline-flex items-center justify-center px-8 py-3.5 text-base font-semibold text-white transition-all bg-cyan-500 rounded-lg hover:bg-cyan-600"
                                    @click.prevent="scrollToSection('contact')"
                                    >{{ copy.ctaPrimary }}</a
                                >
                                <a
                                    href="#solutions"
                                    class="inline-flex items-center justify-center px-8 py-3.5 text-base font-medium text-slate-700 transition-all bg-white border border-slate-200 rounded-lg hover:bg-slate-50 hover:text-cyan-600"
                                    @click.prevent="scrollToSection('solutions')"
                                    >{{ copy.ctaSecondary }}</a
                                >
                            </div>
                        </div>

                        <div
                            class="relative mx-auto w-full max-w-[600px] lg:max-w-none perspective-1000"
                        >
                            <div
                                class="hero-dashboard relative bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform rotate-y-minus-6 rotate-x-6"
                            >
                                <div class="bg-slate-50 border-b border-slate-100 px-4 py-3"></div>
                                <div class="p-6 grid grid-cols-12 gap-6 bg-slate-50/50">
                                    <div class="col-span-3 space-y-3">
                                        <div class="h-8 w-8 bg-cyan-500 rounded-lg mb-6"></div>
                                        <div class="h-2 w-16 bg-slate-200 rounded"></div>
                                    </div>
                                    <div class="col-span-9 space-y-6">
                                        <div class="grid grid-cols-3 gap-4">
                                            <div
                                                class="bg-white p-3 rounded-lg border border-slate-100"
                                            >
                                                <div class="h-6 w-16 bg-slate-800 rounded"></div>
                                            </div>
                                            <div
                                                class="bg-white p-3 rounded-lg border border-slate-100"
                                            >
                                                <div class="h-6 w-16 bg-cyan-500 rounded"></div>
                                            </div>
                                            <div
                                                class="bg-white p-3 rounded-lg border border-slate-100"
                                            >
                                                <div class="h-6 w-16 bg-slate-800 rounded"></div>
                                            </div>
                                        </div>
                                        <div
                                            class="bg-white p-4 rounded-lg border border-slate-100 h-32 flex items-end gap-2"
                                        >
                                            <div
                                                class="w-full bg-cyan-100 rounded-t-sm h-[40%]"
                                            ></div>
                                            <div
                                                class="w-full bg-cyan-200 rounded-t-sm h-[70%]"
                                            ></div>
                                            <div
                                                class="w-full bg-cyan-500 rounded-t-sm h-[50%]"
                                            ></div>
                                            <div
                                                class="w-full bg-slate-800 rounded-t-sm h-[80%]"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white border-y border-slate-200">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 text-center">
                        <div
                            v-for="item in copy.trust"
                            :key="item.label"
                            class="animate-on-scroll rounded-2xl border border-slate-200 bg-slate-50 p-6"
                        >
                            <div class="text-3xl sm:text-4xl font-bold text-slate-900">
                                {{ item.value }}
                            </div>
                            <div class="mt-2 text-sm font-semibold text-slate-600">
                                {{ item.label }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-y border-slate-200 bg-white py-12">
                <div class="container mx-auto px-4 sm:px-6">
                    <p
                        class="text-center text-sm font-semibold text-slate-500 uppercase tracking-wider mb-8"
                    >
                        {{ copy.builtFor }}
                    </p>
                    <p class="max-w-3xl mx-auto text-center text-slate-600 mb-10">
                        {{ copy.stackDesc }}
                    </p>
                    <div
                        class="flex flex-wrap justify-center items-center gap-x-12 gap-y-8 opacity-75"
                    >
                        <div class="flex flex-col items-center gap-2">
                            <UIcon name="i-logos-php" class="text-3xl" /><span
                                class="text-xs font-semibold text-slate-500"
                                >PHP</span
                            >
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <UIcon name="i-logos-laravel" class="text-3xl" /><span
                                class="text-xs font-semibold text-slate-500"
                                >Laravel</span
                            >
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <UIcon name="i-logos-nuxt-icon" class="text-3xl" /><span
                                class="text-xs font-semibold text-slate-500"
                                >Nuxt</span
                            >
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <UIcon name="i-logos-vue" class="text-3xl" /><span
                                class="text-xs font-semibold text-slate-500"
                                >Vue.js</span
                            >
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <UIcon name="i-logos-python" class="text-3xl" /><span
                                class="text-xs font-semibold text-slate-500"
                                >Python</span
                            >
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <UIcon name="i-logos-postgresql" class="text-3xl" /><span
                                class="text-xs font-semibold text-slate-500"
                                >PostgreSQL</span
                            >
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <UIcon name="i-logos-redis" class="text-3xl" /><span
                                class="text-xs font-semibold text-slate-500"
                                >Redis/Valkey</span
                            >
                        </div>
                        <div class="flex flex-col items-center gap-2">
                            <UIcon name="i-logos-wordpress-icon" class="text-3xl" /><span
                                class="text-xs font-semibold text-slate-500"
                                >WordPress</span
                            >
                        </div>
                    </div>
                </div>
            </section>

            <section id="solutions" class="py-20 lg:py-28 bg-slate-50">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 mb-4">
                            {{ copy.solutionsTitle }}
                        </h2>
                        <p class="text-lg text-slate-600">{{ copy.solutionsDesc }}</p>
                    </div>
                    <div class="grid lg:grid-cols-3 gap-8">
                        <div
                            v-for="(solution, index) in copy.solutions"
                            :key="solution.title"
                            class="animate-on-scroll group bg-white rounded-2xl p-2 border border-slate-200 flex flex-col"
                        >
                            <div
                                class="bg-slate-100 rounded-xl h-48 flex items-center justify-center"
                            >
                                <UIcon
                                    :name="
                                        index === 0
                                            ? 'i-logos-whatsapp-icon'
                                            : index === 1
                                              ? 'i-heroicons-computer-desktop'
                                              : 'i-heroicons-shield-check'
                                    "
                                    class="text-6xl text-slate-300"
                                />
                            </div>
                            <div class="p-6 flex-grow">
                                <h3 class="text-xl font-bold text-slate-900 mb-2">
                                    {{ solution.title }}
                                </h3>
                                <p class="text-slate-600 mb-4">{{ solution.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="services" class="py-20 lg:py-28 bg-white">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 mb-4">
                            {{ copy.servicesTitle }}
                        </h2>
                        <p class="text-lg text-slate-600">{{ copy.servicesDesc }}</p>
                    </div>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div
                            v-for="service in copy.services"
                            :key="service.title"
                            class="animate-on-scroll flex flex-col items-start p-6 rounded-2xl hover:bg-slate-50 border border-slate-100"
                        >
                            <div
                                class="w-12 h-12 rounded-xl bg-cyan-50 border border-cyan-100 text-cyan-600 flex items-center justify-center mb-4"
                            >
                                <UIcon :name="service.icon" class="text-xl" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">
                                {{ service.title }}
                            </h3>
                            <p class="text-slate-600 leading-relaxed mb-4">{{ service.desc }}</p>
                            <ul class="space-y-2 text-sm text-slate-500 mb-6">
                                <li
                                    v-for="point in service.points"
                                    :key="point"
                                    class="flex items-center"
                                >
                                    <UIcon name="i-heroicons-check" class="text-cyan-500 mr-2" />{{
                                        point
                                    }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section id="process" class="py-20 bg-slate-50 border-t border-slate-200">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl font-bold text-slate-900 mb-4">
                            {{ copy.processTitle }}
                        </h2>
                        <p class="text-slate-600">{{ copy.processDesc }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div
                            v-for="(step, index) in copy.processSteps"
                            :key="step.title"
                            class="animate-on-scroll bg-white p-6 rounded-xl shadow-sm border border-slate-100 text-center"
                        >
                            <div
                                class="w-16 h-16 mx-auto bg-slate-900 text-white rounded-full flex items-center justify-center text-2xl font-bold mb-4"
                            >
                                {{ index + 1 }}
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 mb-2">{{ step.title }}</h3>
                            <p class="text-sm text-slate-500">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="ai-ethics" class="py-20 lg:py-28 bg-white border-t border-slate-200">
                <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-3xl mx-auto mb-16">
                        <div
                            class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium text-cyan-700 bg-cyan-50 border border-cyan-100 mb-6"
                        >
                            {{ copy.aiBadge }}
                        </div>
                        <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 mb-4">
                            {{ copy.aiTitle }}
                        </h2>
                        <p class="text-lg text-slate-600">{{ copy.aiDesc }}</p>
                    </div>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                        <div
                            v-for="item in copy.aiItems"
                            :key="item.title"
                            class="animate-on-scroll p-6 rounded-2xl border border-slate-200 bg-slate-50"
                        >
                            <h3 class="text-lg font-bold text-slate-900 mb-2">{{ item.title }}</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ item.desc }}</p>
                        </div>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-slate-500 mb-4">
                            {{ copy.aiFoot }}
                            <a
                                :href="localizedPath('/terms-of-service')"
                                class="text-cyan-600 hover:text-cyan-500 transition-colors font-medium"
                                >{{ copy.terms }}</a
                            >
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <footer id="contact" class="bg-slate-900 text-slate-300 py-12 border-t border-slate-800">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-4 gap-8 mb-8">
                    <div class="col-span-1 md:col-span-2">
                        <span class="text-2xl font-bold text-white tracking-tight mb-4 block">
                            {{ appName }}<span class="text-cyan-600">.</span>
                        </span>
                        <p class="text-slate-400 mb-6 max-w-sm">{{ copy.footerTagline }}</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">{{ copy.company }}</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <a
                                    href="#home"
                                    class="hover:text-cyan-400 transition-colors"
                                    @click.prevent="scrollToSection('home')"
                                    >{{ copy.home }}</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#solutions"
                                    class="hover:text-cyan-400 transition-colors"
                                    @click.prevent="scrollToSection('solutions')"
                                    >{{ navItems[0].label }}</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#contact"
                                    class="hover:text-cyan-400 transition-colors"
                                    @click.prevent="scrollToSection('contact')"
                                    >{{ copy.contact }}</a
                                >
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">{{ copy.legal }}</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <a
                                    :href="localizedPath('/privacy-policy')"
                                    class="hover:text-cyan-400 transition-colors"
                                    >{{ copy.privacy }}</a
                                >
                            </li>
                            <li>
                                <a
                                    :href="localizedPath('/terms-of-service')"
                                    class="hover:text-cyan-400 transition-colors"
                                    >{{ copy.terms }}</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#ai-ethics"
                                    class="hover:text-cyan-400 transition-colors"
                                    @click.prevent="scrollToSection('ai-ethics')"
                                    >{{ copy.aiEthics }}</a
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
                            {{ copy.aiCompliance }}
                        </span>
                    </div>
                    <div
                        class="flex flex-col md:flex-row justify-between items-center text-sm text-slate-500"
                    >
                        <p>
                            &copy; {{ new Date().getFullYear() }} {{ appName }}.
                            {{ copy.rightsReserved }}
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
            @click="scrollToSection('home')"
        />
    </div>
</template>

<style scoped>
.perspective-1000 {
    perspective: 1000px;
}

.rotate-y-minus-6 {
    transform: rotateY(-6deg);
}

.rotate-x-6 {
    transform: rotateX(6deg);
}

.flip-enter-active,
.flip-leave-active {
    transition:
        opacity 0.35s ease,
        transform 0.35s ease;
}

.flip-enter-from {
    opacity: 0;
    transform: translateY(14px);
}

.flip-leave-to {
    opacity: 0;
    transform: translateY(-14px);
}
</style>
