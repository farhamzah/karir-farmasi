import { Head, Link } from '@inertiajs/react';
import Brand from '../components/Brand';

type Job = {
    reference: string;
    title: string;
    employer: string;
    city: string | null;
    work_mode: string;
    employment_type: string;
    expires_at: string | null;
    tags: string[];
};

type Event = {
    slug: string;
    title: string;
    organizer: string;
    event_type: string;
    starts_at: string;
    location: string | null;
    topics: string[];
};

type HomeProps = {
    identityStatus: 'unavailable' | 'connected';
    environmentLabel: string | null;
    jobs: Job[];
    events: Event[];
    opportunityCounts: { jobs: number; events: number };
};

const benefits = [
    { icon: '01', title: 'Profil profesional', text: 'Simpan pendidikan, pengalaman, kompetensi, event, dan karya dalam satu profil yang Anda kendalikan.' },
    { icon: '02', title: 'CV yang siap bergerak', text: 'Susun beberapa CV dari satu profil, pilih lima template, lalu publikasikan versi yang paling relevan.' },
    { icon: '03', title: 'Peluang tepercaya', text: 'Temukan lowongan dan agenda pengembangan karier yang dikurasi untuk ekosistem Farmasi UBP.' },
];

const readable = (value: string) => value.replaceAll('_', ' ').replace(/w/g, (letter) => letter.toUpperCase());

export default function Home({ identityStatus, environmentLabel, jobs, events, opportunityCounts }: HomeProps) {
    return <><Head title="Beranda" /><main className="landing-page landing-premium" id="top">
        <header className="site-header"><Brand /><nav aria-label="Navigasi utama"><a href="#peluang">Peluang terbaru</a><a href="#manfaat">Manfaat</a><a href="#cara-kerja">Cara kerja</a></nav><div className="landing-header-actions"><Link className="ghost-button" href="/company/login">Portal perusahaan</Link><Link className="ghost-button" href="/login">Masuk</Link><Link className="primary-button" href="/register">Daftar alumni</Link></div></header>

        <section className="hero hero-redesign home-opportunity-hero">
            <div className="hero-copy"><div className="hero-badges">{environmentLabel && <span className="test-badge">{environmentLabel}</span>}<span className="soft-badge">Ekosistem Karier Farmasi UBP</span></div><p className="eyebrow">TALENTA FARMASI · PELUANG BERDAMPAK</p><h1>Karier farmasi<br /><em>dimulai lebih dekat.</em></h1><p className="lead">Satu ruang untuk membangun profil profesional, menemukan kesempatan, mengikuti event, dan mempertemukan alumni Farmasi UBP dengan perusahaan yang tepat.</p><div className="hero-actions"><Link className="primary-button" href="/register">Bangun profil alumni <span>→</span></Link><Link className="secondary-button" href="/company/register">Bergabung sebagai perusahaan</Link><Link href="/login" className="quiet-link">Saya sudah punya akun</Link></div><div className="trust-row"><span><b>{opportunityCounts.jobs}</b> lowongan aktif</span><span><b>{opportunityCounts.events}</b> event mendatang</span><span><b>5</b> template CV premium</span></div></div>
            <div className="hero-art opportunity-board" aria-label="Ringkasan peluang SAFA KARIR"><div className="hero-glow" /><div className="opportunity-console"><div className="console-top"><span><i />SAFA CAREER DESK</span><small>LIVE OPPORTUNITIES</small></div><div className="console-title"><p>Farmasi UBP Career Network</p><strong>Temukan ruang<br />untuk bertumbuh.</strong></div><div className="console-metrics"><article><span>LOWONGAN</span><b>{opportunityCounts.jobs.toString().padStart(2, '0')}</b><small>aktif & terkurasi</small></article><article><span>EVENT</span><b>{opportunityCounts.events.toString().padStart(2, '0')}</b><small>agenda berikutnya</small></article></div><div className="console-ticker"><span>PROFIL</span><i /><span>CV</span><i /><span>EVENT</span><i /><span>PELUANG</span></div></div><span className="floating-note note-one">Privasi kandidat terjaga</span><span className="floating-note note-two">Khusus ekosistem Farmasi UBP</span></div>
        </section>

        <section className="opportunity-section" id="peluang"><div className="opportunity-heading"><div><p className="eyebrow">PELUANG TERBARU</p><h2>Lihat yang sedang terbuka.</h2></div><p>Informasi ringkas dapat dilihat langsung. Alumni masuk untuk membuka detail dan melamar; perusahaan masuk untuk mengelola rekrutmen.</p></div><div className="home-feed-grid">
            <div className="home-feed"><div className="feed-header"><span>LOWONGAN AKTIF</span><b>{opportunityCounts.jobs.toString().padStart(2, '0')}</b></div>{jobs.length ? jobs.map((job) => <article className="home-job-card" key={job.reference}><div className="card-symbol">↗</div><div><small>{job.employer}</small><h3>{job.title}</h3><p>{[job.city, readable(job.work_mode), readable(job.employment_type)].filter(Boolean).join(' · ')}</p><div className="home-tags">{job.tags.slice(0, 3).map((tag) => <span key={tag}>{tag}</span>)}</div></div><Link href="/login">Lihat detail</Link></article>) : <div className="home-empty"><span>◎</span><h3>Lowongan baru sedang dikurasi.</h3><p>Masuk sebagai alumni untuk menyiapkan profil dan CV lebih dulu.</p></div>}<Link className="feed-footer-link" href="/login">Masuk untuk melihat semua lowongan →</Link></div>
            <div className="home-feed event-feed"><div className="feed-header"><span>EVENT & SEMINAR</span><b>{opportunityCounts.events.toString().padStart(2, '0')}</b></div>{events.length ? events.map((event) => <article className="home-event-card" key={event.slug}><time>{event.starts_at}</time><div><small>{readable(event.event_type)} · {event.organizer}</small><h3>{event.title}</h3><p>{event.location || 'Informasi lokasi tersedia setelah masuk.'}</p><div className="home-tags">{event.topics.slice(0, 3).map((topic) => <span key={topic}>{topic}</span>)}</div></div><Link href="/login">Ikuti event</Link></article>) : <div className="home-empty"><span>✦</span><h3>Agenda berikutnya sedang disiapkan.</h3><p>Event kampus dan sertifikat keikutsertaan akan tampil di sini.</p></div>}<Link className="feed-footer-link" href="/login">Masuk untuk melihat seluruh event →</Link></div>
        </div></section>

        <section className="landing-section" id="manfaat"><div className="section-kicker"><div><p className="eyebrow">DIBUAT UNTUK LANGKAH BERIKUTNYA</p><h2>Satu ekosistem,<br />dua pintu peluang.</h2></div><p>Alumni membangun bukti kompetensi dan mengatur keterbukaan profil. Perusahaan terverifikasi menemukan talenta berdasarkan kebutuhan yang terstruktur.</p></div><div className="benefit-grid">{benefits.map((item) => <article key={item.icon}><span>{item.icon}</span><h3>{item.title}</h3><p>{item.text}</p></article>)}</div></section>

        <section className="workflow-section" id="cara-kerja"><div><p className="eyebrow">MUDAH DIMULAI</p><h2>Dari kampus menuju kesempatan yang lebih luas.</h2></div><ol><li><span>1</span><div><strong>Daftar dengan data minimum</strong><p>Admin kampus melakukan verifikasi tanpa menjadikan email atau OTP sebagai gate alumni.</p></div></li><li><span>2</span><div><strong>Bangun rekam jejak profesional</strong><p>Profil, CV, event, sertifikat, dan portofolio tersusun di sistem karier tanpa mewajibkan pembaruan profil Core.</p></div></li><li><span>3</span><div><strong>Temukan kecocokan</strong><p>Alumni memilih peluang; perusahaan terverifikasi mencari kandidat yang sudah memberi izin.</p></div></li></ol></section>

        <section className="home-company-callout"><div><p className="eyebrow">UNTUK PERUSAHAAN</p><h2>Temukan talenta farmasi<br /><em>dengan konteks yang lebih utuh.</em></h2><p>Cari kompetensi, pengalaman, sertifikat, dan minat karier kandidat yang telah memilih untuk ditemukan.</p></div><div className="company-callout-actions"><Link className="primary-button" href="/company/register">Daftarkan perusahaan</Link><Link className="secondary-button" href="/company/login">Masuk ke portal</Link><small>Verifikasi admin diperlukan sebelum pencarian kandidat aktif.</small></div></section>

        <footer><span>© 2026 SAFA KARIR · Farmasi UBP</span><span className="identity-health">Identitas {identityStatus === 'connected' ? 'terhubung' : 'belum terhubung'} · privasi sejak awal</span></footer>
    </main></>;
}


