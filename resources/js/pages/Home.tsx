import { FormEvent, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Brand from '../components/Brand';

type Job = { reference: string; title: string; employer: string; city: string | null; work_mode: string; employment_type: string; expires_at: string | null; tags: string[] };
type Event = { slug: string; title: string; organizer: string; event_type: string; starts_at: string; location: string | null; topics: string[]; flyer_url: string | null; flyer_alt_text: string };
type Actor = { display_name: string; roles: string[]; capabilities: string[] };
type HomeProps = { identityStatus: 'unavailable' | 'connected'; environmentLabel: string | null; jobs: Job[]; events: Event[]; opportunityCounts: { jobs: number; events: number } };
type SharedProps = { auth?: { actor?: Actor } };

const featureCards = [
    ['↗', 'Lowongan kerja', 'Temukan peluang yang dikurasi untuk talenta farmasi.'],
    ['□', 'Event & seminar', 'Ikuti agenda pengembangan diri dan perluas jejaring.'],
    ['◎', 'Direktori alumni', 'Kenali rekan lintas angkatan dalam ruang yang aman.'],
    ['▤', 'Profil & CV', 'Bangun CV relevan dari satu profil profesional.'],
    ['◇', 'Portofolio & sertifikat', 'Tampilkan karya dan bukti kompetensi pilihan Anda.'],
    ['⌖', 'Tracer study', 'Bagikan perjalanan karier untuk pengembangan alumni.'],
];

const readable = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase());

function HomeHeader({ actor }: { actor?: Actor }) {
    const [open, setOpen] = useState(false);
    const candidate = actor?.roles.includes('kandidat-karir');
    const staff = actor?.roles.some(role => ['admin-karir', 'petugas-karir', 'viewer-karir'].includes(role));
    const accountHref = candidate ? '/dashboard' : staff ? '/staff' : '/login';
    const accountLabel = candidate ? 'Ruang alumni' : staff ? 'Ruang pengelola' : 'Masuk';
    return <header className="home-v2-header">
        <Brand context="FARMASI UBP" />
        <button className="home-v2-menu" type="button" aria-expanded={open} aria-controls="home-nav" onClick={() => setOpen(!open)}><span /><span /><span /><b>Menu</b></button>
        <nav id="home-nav" className={open ? 'is-open' : ''} aria-label="Navigasi utama"><a href="#top" onClick={() => setOpen(false)}>Beranda</a><a href="#lowongan" onClick={() => setOpen(false)}>Lowongan</a><a href="#event" onClick={() => setOpen(false)}>Event</a><a href="#alumni" onClick={() => setOpen(false)}>Alumni</a><a href="#fitur" onClick={() => setOpen(false)}>Fitur</a><a href="#tentang" onClick={() => setOpen(false)}>Tentang</a><Link className="home-v2-mobile-account" href={accountHref}>{accountLabel}</Link></nav>
        <div className="home-v2-actions"><Link className="home-v2-login" href={accountHref}>{accountLabel}</Link>{!actor && <Link className="home-v2-register" href="/register">Daftar alumni</Link>}</div>
    </header>;
}

function JobCard({ job, signedIn }: { job: Job; signedIn: boolean }) {
    return <article className="home-v2-job-card"><div className="home-v2-company-mark">{job.employer.slice(0, 2).toUpperCase()}</div><div className="home-v2-card-body"><div className="home-v2-card-top"><span>{job.employer}</span><b>{readable(job.employment_type)}</b></div><h3>{job.title}</h3><p>⌖ {job.city || 'Lokasi diinformasikan'} · {readable(job.work_mode)}</p><div className="home-v2-tags">{job.tags.slice(0, 3).map(tag => <span key={tag}>{tag}</span>)}</div></div><Link aria-label={`Lihat ${job.title}`} href={signedIn ? `/jobs/${job.reference}` : '/login'}>→</Link></article>;
}

function EventCard({ event, signedIn }: { event: Event; signedIn: boolean }) {
    return <article className="home-v2-event-card">{event.flyer_url ? <img src={event.flyer_url} alt={event.flyer_alt_text} /> : <div className="home-v2-date"><b>{event.starts_at.split(' ')[0]}</b><span>{event.starts_at.split(' ').slice(1).join(' ')}</span></div>}<div className="home-v2-card-body"><div className="home-v2-card-top"><span>{readable(event.event_type)}</span><b>{event.organizer}</b></div><h3>{event.title}</h3><p>⌖ {event.location || 'Detail lokasi tersedia di halaman event'}</p><div className="home-v2-tags">{event.topics.slice(0, 3).map(topic => <span key={topic}>{topic}</span>)}</div></div><Link aria-label={`Lihat ${event.title}`} href={signedIn ? `/events/${event.slug}` : '/login'}>→</Link></article>;
}

export default function Home({ identityStatus, environmentLabel, jobs, events, opportunityCounts }: HomeProps) {
    const actor = usePage<SharedProps>().props.auth?.actor;
    const isCandidate = actor?.roles.includes('kandidat-karir') ?? false;
    const [query, setQuery] = useState('');
    const submitSearch = (event: FormEvent) => { event.preventDefault(); if (isCandidate) router.get('/jobs', query.trim() ? { q: query.trim() } : {}); else router.visit('/login'); };

    return <><Head title="SAFA KARIR — Alumni Farmasi UBP" /><main className="home-v2" id="top">
        <HomeHeader actor={actor} />
        <section className="home-v2-hero" aria-labelledby="home-title">
            <img className="home-v2-hero-image" src="/images/safa-home-hero-v1.webp" alt="Alumni farmasi berjalan di lingkungan kampus" fetchPriority="high" /><div className="home-v2-hero-overlay" />
            <div className="home-v2-hero-copy"><div className="home-v2-badges">{environmentLabel && <span>{environmentLabel}</span>}<span>Platform resmi Farmasi UBP</span></div><p className="home-v2-eyebrow">ALUMNI · KARIER · MASA DEPAN</p><h1 id="home-title">Koneksi hari ini,<br /><em>karier masa depan</em><br />bersama alumni.</h1><p>Bangun profil profesional, temukan peluang kerja, dan terus berkembang dalam ekosistem alumni Farmasi UBP.</p>
                <form className="home-v2-search" onSubmit={submitSearch}><label className="sr-only" htmlFor="home-job-search">Cari lowongan</label><span>⌕</span><input id="home-job-search" value={query} onChange={e => setQuery(e.target.value)} placeholder="Cari posisi, perusahaan, atau kompetensi" /><button type="submit">Cari</button></form><div className="home-v2-quick-tags"><span>Populer:</span><span>Apoteker</span><span>QA/QC</span><span>Regulatory</span><span>Industri</span></div>
            </div>
            <aside className="home-v2-hero-card"><span>✦</span><div><b>{actor ? `Selamat datang, ${actor.display_name.split(' ')[0]}` : 'Bergabung dengan alumni UBP'}</b><p>{actor ? 'Lanjutkan perjalanan karier Anda.' : 'Mulai profil profesional yang siap dibagikan.'}</p></div><Link href={actor ? (isCandidate ? '/dashboard' : '/staff') : '/register'}>→</Link></aside>
        </section>

        <section className="home-v2-feature-strip" id="fitur" aria-label="Fitur SAFA KARIR">{featureCards.map(([icon, title, text]) => <article key={title}><span>{icon}</span><div><h2>{title}</h2><p>{text}</p></div></article>)}</section>

        <section className="home-v2-opportunities" aria-labelledby="opportunity-title"><div className="home-v2-section-head"><div><p className="home-v2-eyebrow">PELUANG TERBARU</p><h2 id="opportunity-title">Kesempatan yang dekat,<br />langkah yang lebih mantap.</h2></div><p>Informasi dari ekosistem kampus dan mitra terverifikasi, disajikan ringkas agar mudah dipilih.</p></div><div className="home-v2-feed-grid">
            <section id="lowongan"><div className="home-v2-feed-title"><div><span>Lowongan aktif</span><b>{opportunityCounts.jobs}</b></div><Link href={isCandidate ? '/jobs' : '/login'}>Lihat semua →</Link></div><div className="home-v2-feed-list">{jobs.length ? jobs.map(job => <JobCard key={job.reference} job={job} signedIn={isCandidate} />) : <div className="home-v2-empty"><b>Lowongan sedang dikurasi.</b><p>Silakan kembali lagi untuk melihat peluang terbaru.</p></div>}</div></section>
            <section id="event"><div className="home-v2-feed-title"><div><span>Event & seminar</span><b>{opportunityCounts.events}</b></div><Link href={isCandidate ? '/events' : '/login'}>Lihat semua →</Link></div><div className="home-v2-feed-list">{events.length ? events.map(item => <EventCard key={item.slug} event={item} signedIn={isCandidate} />) : <div className="home-v2-empty"><b>Agenda berikutnya sedang disiapkan.</b><p>Event yang telah dipublikasikan akan tampil di sini.</p></div>}</div></section>
        </div></section>

        <section className="home-v2-story" id="alumni"><div><p className="home-v2-eyebrow">PERJALANAN ALUMNI</p><h2>Satu profil untuk setiap langkah karier.</h2><p>Mulai dari rekam jejak akademik, CV untuk peluang tertentu, hingga event dan sertifikat yang memperkuat portofolio Anda.</p><Link className="home-v2-primary" href={actor ? (isCandidate ? '/profile' : '/staff') : '/register'}>{actor ? 'Lanjutkan perjalanan' : 'Mulai sekarang'} <span>→</span></Link></div><ol><li><span>01</span><div><b>Bangun profil</b><p>Isi sekali, gunakan kembali untuk banyak CV.</p></div></li><li><span>02</span><div><b>Pilih kesempatan</b><p>Temukan lowongan dan event yang relevan.</p></div></li><li><span>03</span><div><b>Tampilkan kemampuan</b><p>Bagikan hanya informasi yang Anda izinkan.</p></div></li></ol></section>

        <section className="home-v2-company" id="tentang"><div><p className="home-v2-eyebrow">UNTUK PERUSAHAAN</p><h2>Temukan talenta farmasi<br />dengan konteks yang lebih utuh.</h2><p>Perusahaan terverifikasi dapat mempublikasikan lowongan dan menemukan kandidat yang telah memilih untuk dapat ditemukan.</p></div><div><Link className="home-v2-primary" href="/company/register">Daftarkan perusahaan <span>→</span></Link><Link className="home-v2-secondary" href="/company/login">Masuk portal perusahaan</Link><small>Verifikasi admin diperlukan sebelum akses pencarian talenta aktif.</small></div></section>
        <section className="home-v2-final"><div><span>FARMASI UBP · SAFA KARIR</span><h2>Siapkan langkah berikutnya<br />bersama komunitas alumni.</h2></div><Link href={actor ? (isCandidate ? '/dashboard' : '/staff') : '/register'}>{actor ? 'Buka ruang Anda' : 'Daftar sebagai alumni'} <span>→</span></Link></section>
        <footer className="home-v2-footer"><Brand context="FARMASI UBP" /><p>Ruang karier resmi untuk alumni Farmasi Universitas Buana Perjuangan Karawang.</p><nav><a href="#lowongan">Lowongan</a><a href="#event">Event</a><a href="#alumni">Alumni</a><a href="#tentang">Tentang</a></nav><small>© 2026 SAFA KARIR · Identitas {identityStatus === 'connected' ? 'terhubung' : 'belum terhubung'} · Privasi sejak awal</small></footer>
    </main></>;
}
