@php
    $isIdStatic = $isIdStatic ?? str_starts_with(app()->getLocale(), 'id');
@endphp

<header>
    <h1>{{ config('app.name') }}</h1>
    <p>
        {{ $isIdStatic ? 'Mode tanpa JavaScript aktif. Berikut konten utama situs (Landing Page, Syarat & Ketentuan Layanan, dan Kebijakan Privasi).' : 'JavaScript-free mode is active. Below is the core site content (Landing Page, Terms of Service, and Privacy Policy).' }}
    </p>
    <nav>
        <a href="/{{ $isIdStatic ? '?lang=id' : '?lang=en' }}">{{ $isIdStatic ? 'Beranda' : 'Home' }}</a>
        |
        <a href="/terms-of-service?lang={{ $isIdStatic ? 'id' : 'en' }}">{{ $isIdStatic ? 'Syarat dan Ketentuan Layanan' : 'Terms of Service' }}</a>
        |
        <a href="/privacy-policy?lang={{ $isIdStatic ? 'id' : 'en' }}">{{ $isIdStatic ? 'Kebijakan Privasi' : 'Privacy Policy' }}</a>
    </nav>
</header>

<hr>

@if (!$isIdStatic)
    <section>
        <h2>Landing Page</h2>
        <p><strong>Enterprise Digital Transformation Partner</strong></p>
        <p><strong>High-Availability Systems that Automate, Scale, Stabilize, Orchestrate, and Optimize Operations</strong></p>
        <p>
            We engineer operational automation with complex business logic, built for scalability and real-world traffic.
            For teams tired of toy apps, we deliver robust systems that reduce admin workload and protect uptime.
        </p>
        <p>
            <strong>Highlights:</strong>
        </p>
        <ul>
            <li>Enterprise-grade engineering track record</li>
            <li>Thousands of users supported in production</li>
            <li>Multiple enterprise modules shipped</li>
        </ul>
        <p><strong>Built for Security, Speed, and Scalability</strong></p>
        <p>
            We choose Laravel and Vue for secure, maintainable product foundations
            and Python for integrations and data workflows.
        </p>

        <h3>Selected Core Team Experience</h3>
        <ul>
            <li>
                <strong>High-Volume CRM & Communication Automation:</strong>
                Built to support large numbers of users with automated messaging workflows, reliable delivery, and stability during traffic spikes.
            </li>
            <li>
                <strong>Integrated Point-of-Sale (POS) & Inventory Ecosystem:</strong>
                Hardware-ready POS with real-time stock synchronization and reconciliation-grade accuracy for retail, logistics, and F&B.
            </li>
            <li>
                <strong>Secure Public Sector Data Management:</strong>
                Security-first architecture with strict data controls and complex calculation logic for regulated data environments.
            </li>
        </ul>

        <h3>Our Expertise</h3>
        <ul>
            <li>
                <strong>Web App Development:</strong>
                Custom web applications built on modern frameworks with high performance and scalability. (SaaS Platforms, Internal Dashboards)
            </li>
            <li>
                <strong>Mobile Solutions:</strong>
                Native and cross-platform mobile applications with production-grade delivery quality. (React Native / Flutter, PWA Development)
            </li>
            <li>
                <strong>WhatsApp Automation:</strong>
                AI-driven chatbot and notification automation workflows. (AI Virtual Agents, Customer Support Automation)
            </li>
            <li>
                <strong>WordPress Solutions:</strong>
                Custom WordPress installations and bespoke themes built for performance, SEO, and ease of content management.
                (Custom Themes & Templates, WooCommerce & Plugin Integration)
            </li>
        </ul>

        <h3>Our Workflow</h3>
        <ol>
            <li><strong>Discovery:</strong> Requirement analysis and strategic planning.</li>
            <li><strong>Design:</strong> UI/UX prototyping and architecture design.</li>
            <li><strong>Development:</strong> Agile sprints and quality assurance testing.</li>
            <li><strong>Deployment:</strong> Launch, monitoring, and scale-up.</li>
        </ol>

        <h3>AI-Assisted. Human-Accountable.</h3>
        <p>
            We leverage state-of-the-art Agentic AI workflows to accelerate delivery and stay transparent about their boundaries.
        </p>
        <ul>
            <li><strong>AI-Augmented Engineering:</strong> AI accelerates prototyping, code generation, and design quality as a force multiplier for engineers.</li>
            <li><strong>Human-in-the-Loop (HITL):</strong> Every AI-assisted output is reviewed by a qualified engineer before release.</li>
            <li><strong>Your Data Stays Yours:</strong> We do not use proprietary client or user data to train public third-party AI models.</li>
            <li><strong>IP Clarity & Ownership:</strong> Ownership terms are clear for platform IP and custom deliverables.</li>
            <li><strong>Ethical AI Governance:</strong> AI governance follows ethical principles of transparency and accountability.</li>
            <li><strong>Best Effort & Accountability:</strong> We commit to prompt remediation and professional responsibility for all final outputs.</li>
        </ul>
        <p>
            For full details, including AI disclosure and limitation of liability, see
            <a href="/terms-of-service?lang=en">Terms of Service Section 11</a>.
        </p>
    </section>

    <hr>

    <section>
        <h2>Terms of Service</h2>
        <p><strong>Last Updated: March 5, 2026</strong></p>
        <p>
            These Terms govern access to and use of our website, software products, applications, and related services.
        </p>
        <ol>
            <li><strong>Definitions</strong> — Defines the service provider, users, and applications.</li>
            <li><strong>License to Use</strong> — Limited, non-exclusive, non-transferable, revocable license; prohibits copying, reverse engineering, derivative works, and unauthorized third-party use.</li>
            <li><strong>Intellectual Property and Ownership</strong> — Service IP remains ours unless agreed otherwise; user content remains owned by users; anonymized aggregate usage data may be used; user feedback may be implemented under royalty-free license.</li>
            <li><strong>Payment and Fees</strong> — Prices may change; payment via authorized channels; valid payment info is required.</li>
            <li><strong>Refund Policy</strong> — Digital products are generally non-refundable after delivery/access, except where required by law; exceptions include unresolved material technical defects and verified duplicate transactions.</li>
            <li><strong>Privacy Policy</strong> — Personal data processing follows our policy at <a href="/privacy-policy?lang=en">/privacy-policy</a>.</li>
            <li><strong>Termination of Service</strong> — Access may be suspended/terminated for material violations, legal compliance, security, or platform integrity.</li>
            <li><strong>Limitation of Liability</strong> — Service provided “as is” and “as available”; liability limited to direct losses where required by law; indirect/consequential losses excluded where permitted.</li>
            <li><strong>Changes to Terms</strong> — Continued use after updates constitutes acceptance.</li>
            <li><strong>Governing Law</strong> — Laws of the Republic of Indonesia.</li>
            <li><strong>AI Disclosure and Ethical Use</strong> — AI-assisted workflows with mandatory HITL review and professional accountability.</li>
            <li><strong>Contact</strong> — Questions can be sent to <a href="mailto:admin@yourdomain.com">admin@yourdomain.com</a>.</li>
        </ol>
        <p>
            <strong>Language Clause:</strong> Terms are available in English and Indonesian. Both are intended to carry equal legal meaning.
            For legal interpretation and enforcement under Indonesian law, the Indonesian version prevails where required by law.
        </p>
        <p>
            Full page: <a href="/terms-of-service?lang=en">/terms-of-service</a>
        </p>
    </section>

    <hr>

    <section>
        <h2>Privacy Policy</h2>
        <p><strong>Last Updated: March 5, 2026</strong></p>
        <p>
            This policy describes how {{ config('app.name') }} collects, uses, stores, and protects personal data in accordance with
            Indonesia Law No. 27 of 2022 concerning Personal Data Protection (UU PDP).
        </p>
        <ol>
            <li>
                <strong>Personal Data We Collect and Why</strong>
                <ul>
                    <li><strong>Data You Provide Directly:</strong> Identity Data (full name, username), Contact Data (email, phone, communication records).</li>
                    <li><strong>Data Collected Automatically:</strong> Usage Data (interaction/activity logs), Device Data (IP, browser, OS, diagnostics).</li>
                    <li><strong>Legal Basis:</strong> Consent, contractual necessity, and legitimate interests.</li>
                </ul>
            </li>
            <li><strong>How We Use Personal Data</strong> — Service delivery, account management, communications, personalization, analytics, and security incident response.</li>
            <li><strong>Data Sharing and Disclosure</strong> — No sale/rental of personal data; disclosure only by consent, legal requirement, or to trusted processors under written obligations.</li>
            <li><strong>Data Subject Rights</strong> — Access, correction, deletion (as applicable), restriction/objection, consent withdrawal, portability (as applicable).</li>
            <li><strong>Data Security</strong> — Technical and organizational safeguards, including access controls, monitoring, and encryption where relevant.</li>
            <li><strong>Data Breach Notification</strong> — Notification to authorities and affected data subjects no later than 3 x 24 hours after awareness, where required by law.</li>
            <li><strong>Data Retention</strong> — Retained only as long as necessary for lawful, contractual, and legal obligations.</li>
            <li><strong>International Data Transfers</strong> — Conducted in accordance with UU PDP and implementing regulations.</li>
            <li><strong>Changes to Policy</strong> — Policy may be updated from time to time.</li>
            <li><strong>Data Protection Officer</strong> — Contact: <a href="mailto:admin@yourdomain.com">admin@yourdomain.com</a> (target response: 14 business days).</li>
            <li><strong>AI Transparency and Ethical Use</strong> — AI-assisted operations with Human-in-the-Loop review; no model training on personal data without explicit legal basis and safeguards; anonymized aggregate analysis may be used for service quality.</li>
            <li><strong>Contact</strong> — {{ config('app.name') }}, <a href="mailto:admin@yourdomain.com">admin@yourdomain.com</a>.</li>
        </ol>
        <p>
            <strong>Language Clause:</strong> This policy is available in English and Indonesian. Both are intended to have the same legal effect.
            For processing and enforcement under Indonesian law, the Indonesian version prevails where required by law.
        </p>
        <p>
            Full page: <a href="/privacy-policy?lang=en">/privacy-policy</a>
        </p>
    </section>
@else
    <section>
        <h2>Landing Page</h2>
        <p><strong>Mitra Transformasi Digital Enterprise</strong></p>
        <p><strong>Sistem High-Availability yang Otomatiskan, Skalakan, Stabilkan, Orkestrasi, dan Optimalkan Operasional Anda</strong></p>
        <p>
            Kami merekayasa otomasi operasional dengan logika bisnis kompleks, dirancang untuk skalabilitas dan trafik nyata.
            Untuk tim yang membutuhkan sistem serius, kami menghadirkan solusi tangguh yang mengurangi beban admin dan menjaga uptime.
        </p>
        <p><strong>Sorotan:</strong></p>
        <ul>
            <li>Rekam jejak engineering berstandar enterprise</li>
            <li>Ribuan pengguna didukung dalam produksi</li>
            <li>Beberapa modul enterprise telah dikirimkan</li>
        </ul>
        <p><strong>Dirancang untuk Keamanan, Kecepatan, dan Skalabilitas</strong></p>
        <p>
            Kami memilih Laravel dan Vue untuk fondasi produk yang aman dan terawat,
            serta Python untuk integrasi dan alur data.
        </p>

        <h3>Pengalaman Inti Tim Terpilih</h3>
        <ul>
            <li>
                <strong>Otomasi CRM & Komunikasi Volume Tinggi:</strong>
                Dibangun untuk mendukung ribuan pengguna dengan alur pesan otomatis, pengiriman andal, dan stabil saat lonjakan trafik.
            </li>
            <li>
                <strong>Ekosistem Point-of-Sale (POS) & Inventori Terintegrasi:</strong>
                POS siap hardware dengan sinkronisasi stok real-time dan akurasi setingkat rekonsiliasi untuk retail, logistik, dan F&B.
            </li>
            <li>
                <strong>Manajemen Data Sektor Publik yang Aman:</strong>
                Arsitektur berfokus keamanan dengan kontrol data ketat dan logika perhitungan kompleks untuk lingkungan data ter-regulasi.
            </li>
        </ul>

        <h3>Keahlian Kami</h3>
        <ul>
            <li>
                <strong>Pengembangan Aplikasi Web:</strong>
                Aplikasi web kustom berbasis framework modern dengan performa dan skalabilitas tinggi. (Platform SaaS, Dashboard Internal)
            </li>
            <li>
                <strong>Solusi Mobile:</strong>
                Aplikasi mobile native dan lintas platform dengan kualitas delivery berstandar produksi. (React Native / Flutter, Pengembangan PWA)
            </li>
            <li>
                <strong>Otomasi WhatsApp:</strong>
                Chatbot berbasis AI dan alur notifikasi otomatis. (Agen Virtual AI, Otomasi Dukungan Pelanggan)
            </li>
            <li>
                <strong>Solusi WordPress:</strong>
                Instalasi WordPress kustom dan tema bespoke yang dioptimalkan untuk performa, SEO, dan kemudahan pengelolaan konten.
                (Tema & Template Kustom, Integrasi WooCommerce & Plugin)
            </li>
        </ul>

        <h3>Alur Kerja Kami</h3>
        <ol>
            <li><strong>Discovery:</strong> Analisis kebutuhan dan perencanaan strategis.</li>
            <li><strong>Desain:</strong> Prototyping UI/UX dan perancangan arsitektur.</li>
            <li><strong>Pengembangan:</strong> Sprint agile dan pengujian quality assurance.</li>
            <li><strong>Deployment:</strong> Peluncuran, pemantauan, dan scale-up.</li>
        </ol>

        <h3>Dibantu AI. Bertanggung Jawab oleh Manusia.</h3>
        <p>
            Kami memanfaatkan alur Agentic AI terkini untuk mempercepat delivery dan tetap transparan terhadap batas penggunaannya.
        </p>
        <ul>
            <li><strong>Engineering Berbantuan AI:</strong> AI mempercepat prototyping, code generation, dan kualitas desain sebagai pengganda kemampuan engineer.</li>
            <li><strong>Human-in-the-Loop (HITL):</strong> Setiap output berbantuan AI ditinjau engineer berkompeten sebelum dirilis.</li>
            <li><strong>Data Anda Tetap Milik Anda:</strong> Kami tidak menggunakan data proprietary klien atau pengguna untuk melatih model AI publik pihak ketiga.</li>
            <li><strong>Kejelasan HKI & Kepemilikan:</strong> Ketentuan kepemilikan jelas untuk HKI platform maupun deliverable kustom.</li>
            <li><strong>Tata Kelola AI Etis:</strong> Tata kelola AI menerapkan prinsip transparansi, akuntabilitas, dan keadilan.</li>
            <li><strong>Best Effort & Akuntabilitas:</strong> Kami berkomitmen pada remediasi cepat dan tanggung jawab profesional atas seluruh output akhir.</li>
        </ul>
        <p>
            Untuk detail lengkap, termasuk pengungkapan AI dan batasan tanggung jawab, lihat
            <a href="/terms-of-service?lang=id">Syarat dan Ketentuan Layanan Bagian 11</a>.
        </p>
    </section>

    <hr>

    <section>
        <h2>Syarat dan Ketentuan Layanan</h2>
        <p><strong>Terakhir Diperbarui: 5 Maret 2026</strong></p>
        <p>
            Syarat ini mengatur akses dan penggunaan situs web, produk perangkat lunak, aplikasi, dan layanan terkait kami.
        </p>
        <ol>
            <li><strong>Definisi</strong> — Menjelaskan penyedia layanan, pengguna, dan aplikasi.</li>
            <li><strong>Lisensi Penggunaan</strong> — Lisensi terbatas, non-eksklusif, tidak dapat dialihkan, dan dapat dicabut; melarang penyalinan, reverse engineering, karya turunan, dan penggunaan pihak ketiga tanpa kewenangan.</li>
            <li><strong>Hak Kekayaan Intelektual dan Kepemilikan</strong> — HKI layanan tetap milik kami kecuali disepakati lain; konten pengguna tetap milik pengguna; data agregat anonim dapat digunakan; umpan balik pengguna dapat diimplementasikan berdasarkan lisensi bebas royalti.</li>
            <li><strong>Pembayaran dan Biaya</strong> — Harga dapat berubah; pembayaran melalui kanal resmi; informasi pembayaran valid wajib diberikan.</li>
            <li><strong>Kebijakan Pengembalian Dana</strong> — Produk digital umumnya tidak dapat dikembalikan setelah diserahkan/diakses, kecuali diwajibkan hukum; pengecualian termasuk cacat teknis material yang tidak terselesaikan dan transaksi ganda terverifikasi.</li>
            <li><strong>Kebijakan Privasi</strong> — Pemrosesan data pribadi mengikuti kebijakan kami di <a href="/privacy-policy?lang=id">/privacy-policy</a>.</li>
            <li><strong>Penghentian Layanan</strong> — Akses dapat ditangguhkan/dihentikan untuk pelanggaran material, kepatuhan hukum, keamanan, atau integritas platform.</li>
            <li><strong>Batasan Tanggung Jawab</strong> — Layanan disediakan “sebagaimana adanya” dan “sebagaimana tersedia”; tanggung jawab dibatasi pada kerugian langsung jika diwajibkan hukum; kerugian tidak langsung/konsekuensial dikecualikan sepanjang diperbolehkan hukum.</li>
            <li><strong>Perubahan Syarat</strong> — Penggunaan berkelanjutan setelah pembaruan berarti persetujuan.</li>
            <li><strong>Hukum yang Berlaku</strong> — Hukum Republik Indonesia.</li>
            <li><strong>Pengungkapan AI dan Penggunaan Etis</strong> — Alur kerja berbantuan AI dengan proses wajib HITL dan akuntabilitas profesional.</li>
            <li><strong>Kontak</strong> — Pertanyaan dapat dikirim ke <a href="mailto:admin@yourdomain.com">admin@yourdomain.com</a>.</li>
        </ol>
        <p>
            <strong>Klausul Bahasa:</strong> Syarat tersedia dalam Bahasa Inggris dan Bahasa Indonesia. Keduanya dimaksudkan memiliki makna hukum setara.
            Untuk interpretasi dan penegakan menurut hukum Indonesia, versi Bahasa Indonesia berlaku sepanjang diwajibkan hukum.
        </p>
        <p>
            Halaman lengkap: <a href="/terms-of-service?lang=id">/terms-of-service</a>
        </p>
    </section>

    <hr>

    <section>
        <h2>Kebijakan Privasi</h2>
        <p><strong>Terakhir Diperbarui: 5 Maret 2026</strong></p>
        <p>
            Kebijakan ini menjelaskan bagaimana {{ config('app.name') }} mengumpulkan, menggunakan, menyimpan, dan melindungi data pribadi sesuai
            Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP).
        </p>
        <ol>
            <li>
                <strong>Data Pribadi yang Dikumpulkan dan Tujuannya</strong>
                <ul>
                    <li><strong>Data yang Diberikan Langsung:</strong> Data Identitas (nama lengkap, nama pengguna), Data Kontak (email, telepon, catatan komunikasi).</li>
                    <li><strong>Data yang Dikumpulkan Otomatis:</strong> Data Penggunaan (interaksi/log aktivitas), Data Perangkat (IP, peramban, OS, diagnostik).</li>
                    <li><strong>Dasar Hukum:</strong> Persetujuan, kebutuhan kontraktual, dan kepentingan yang sah.</li>
                </ul>
            </li>
            <li><strong>Cara Penggunaan Data Pribadi</strong> — Delivery layanan, manajemen akun, komunikasi, personalisasi, analitik, dan respons insiden keamanan.</li>
            <li><strong>Berbagi dan Pengungkapan Data</strong> — Tidak menjual/menyewakan data pribadi; pengungkapan hanya atas persetujuan, perintah hukum, atau kepada pemroses tepercaya dengan kewajiban tertulis.</li>
            <li><strong>Hak Subjek Data</strong> — Akses, perbaikan, penghapusan (sesuai ketentuan), pembatasan/keberatan, pencabutan persetujuan, portabilitas (sesuai ketentuan).</li>
            <li><strong>Keamanan Data</strong> — Pengamanan teknis dan organisasi, termasuk kontrol akses, pemantauan, dan enkripsi jika relevan.</li>
            <li><strong>Pemberitahuan Pelanggaran Data</strong> — Pemberitahuan ke otoritas dan subjek data terdampak paling lambat 3 x 24 jam sejak diketahui, jika diwajibkan hukum.</li>
            <li><strong>Retensi Data</strong> — Data disimpan selama diperlukan untuk tujuan sah, kontraktual, dan kewajiban hukum.</li>
            <li><strong>Transfer Data Internasional</strong> — Dilaksanakan sesuai UU PDP dan aturan pelaksana.</li>
            <li><strong>Perubahan Kebijakan</strong> — Kebijakan dapat diperbarui dari waktu ke waktu.</li>
            <li><strong>Pejabat Pelindungan Data</strong> — Kontak: <a href="mailto:admin@yourdomain.com">admin@yourdomain.com</a> (target tanggapan: 14 hari kerja).</li>
            <li><strong>Transparansi AI dan Penggunaan Etis</strong> — Operasional berbantuan AI dengan review HITL; tidak melatih model dengan data pribadi tanpa dasar hukum eksplisit dan perlindungan memadai; analisis agregat anonim dapat digunakan untuk peningkatan layanan.</li>
            <li><strong>Kontak</strong> — {{ config('app.name') }}, <a href="mailto:admin@yourdomain.com">admin@yourdomain.com</a>.</li>
        </ol>
        <p>
            <strong>Klausul Bahasa:</strong> Kebijakan ini tersedia dalam Bahasa Inggris dan Bahasa Indonesia. Keduanya dimaksudkan memiliki kekuatan hukum yang sama.
            Untuk pemrosesan dan penegakan berdasarkan hukum Indonesia, versi Bahasa Indonesia berlaku sepanjang diwajibkan hukum.
        </p>
        <p>
            Halaman lengkap: <a href="/privacy-policy?lang=id">/privacy-policy</a>
        </p>
    </section>
@endif

<hr>

<footer>
    <p>
        &copy; {{ date('Y') }} {{ config('app.name') }} — {{ $isIdStatic ? 'Seluruh hak dilindungi.' : 'All rights reserved.' }}
    </p>
</footer>
