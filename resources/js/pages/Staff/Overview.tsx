import { Head, Link } from '@inertiajs/react';
import AppHeader from '../../components/AppHeader';
import { ReadOnlyBanner } from '../../components/Ui';

type Actor = { display_name: string; roles: string[]; capabilities: string[] };
type Summary = { scope:string; candidate_profiles:number; profiles_fresh:number; profiles_stale:number; tracer_submitted:number; tracer_response_rate:number; jobs_active:number; jobs_expired:number; applications_total:number; applications_interview:number; applications_offer:number; hires_started:number; companies_active:number; event_participations:number; certificates_issued:number; employer_feedback_count:number };
type DirectoryAccess = { available:boolean; scope:string; role:string };

const overviewMetrics: [keyof Summary, string, string][] = [
    ['candidate_profiles', 'Profil alumni', 'Profil profesional dalam lingkup Anda'],
    ['tracer_response_rate', 'Respons tracer', 'Persentase alumni yang sudah merespons'],
    ['jobs_active', 'Lowongan aktif', 'Peluang yang sedang tayang'],
    ['applications_total', 'Total lamaran', 'Lamaran dari alumni dalam lingkup'],
];
const detailMetrics: [keyof Summary, string][] = [
    ['profiles_fresh', 'Profil diperbarui'], ['profiles_stale', 'Perlu konfirmasi'], ['tracer_submitted', 'Tracer terkirim'],
    ['applications_interview', 'Tahap interview'], ['applications_offer', 'Tahap penawaran'], ['hires_started', 'Mulai bekerja'],
    ['companies_active', 'Perusahaan aktif'], ['event_participations', 'Partisipasi event'], ['certificates_issued', 'Sertifikat terbit'],
    ['employer_feedback_count', 'Umpan balik perusahaan'],
];

export default function Overview({ actor, summary, directoryAccess }: { actor:Actor; summary:Summary; directoryAccess:DirectoryAccess }) {
    const admin = actor.capabilities.includes('tracer.manage');
    const viewer = actor.roles.includes('viewer-karir');
    const nav = [
        { label: 'Ringkasan', href: '/staff', active: true },
        ...(admin ? [{ label: 'Tracer', href: '/admin/tracer' }, { label: 'Import Lowongan', href: '/admin/jobs/import' }] : []),
        ...(directoryAccess.available ? [{ label: 'Talenta Internal', href: '/internal/talent' }] : []),
    ];

    return <><Head title="Dashboard Operasional"/><main className="admin-page app-surface"><AppHeader context="OPERASIONAL KARIER" home="/staff" nav={nav} logout/>
        <section className="operational-shell staff-overview-shell">
            <header className="staff-welcome"><div><p className="eyebrow">DASHBOARD PIMPINAN</p><h1>Selamat datang, {actor.display_name}.</h1><p>Pantau perkembangan alumni, tracer, lowongan, dan rekrutmen dalam satu ringkasan yang mudah dibaca.</p></div><div className={directoryAccess.available ? 'scope-status ready' : 'scope-status pending'}><small>LINGKUP DATA</small><strong>{directoryAccess.available ? (directoryAccess.scope || 'Assignment aktif') : 'Belum ditetapkan'}</strong><span>{directoryAccess.available ? 'Data mengikuti assignment aktif Anda.' : 'Minta administrator SAFA KARIR menetapkan program studi atau fakultas.'}</span></div></header>

            {!directoryAccess.available && viewer && <section className="staff-attention-card"><div className="staff-attention-icon">!</div><div><p className="eyebrow">AKSES BELUM LENGKAP</p><h2>Direktori talenta belum dapat dibuka.</h2><p>Akun Anda sudah masuk sebagai pimpinan, tetapi lingkup program studi atau fakultas belum ditautkan. Demi privasi, sistem belum menampilkan alumni.</p></div><span className="status-dot">Data tetap aman</span></section>}

            <div className="staff-section-heading"><div><p className="eyebrow">RINGKASAN UTAMA</p><h2>Kondisi saat ini</h2></div><span>{directoryAccess.available ? summary.scope : 'Menunggu assignment administrator'}</span></div>
            <div className="metric-grid metric-grid-primary">{overviewMetrics.map(([key, label, description]) => <article key={key}><small>{label}</small><strong>{key === 'tracer_response_rate' ? `${summary[key]}%` : summary[key]}</strong><span>{description}</span></article>)}</div>

            <div className="staff-section-heading compact"><div><p className="eyebrow">RINCIAN OPERASIONAL</p><h2>Aktivitas lainnya</h2></div></div>
            <div className="metric-grid metric-grid-secondary">{detailMetrics.map(([key, label]) => <article key={key}><small>{label}</small><strong>{summary[key]}</strong></article>)}</div>

            {directoryAccess.available && <section className="staff-directory-cta"><div><p className="eyebrow">DIREKTORI TALENTA</p><h2>Lihat alumni dalam lingkup Anda</h2><p>Cari berdasarkan pendidikan, kompetensi, sertifikasi, dan pengalaman tanpa membuka kontak atau dokumen privat.</p></div><Link className="primary-button" href="/internal/talent">Buka direktori</Link></section>}

            {(!viewer || directoryAccess.available) && <div className="report-actions"><div><p className="eyebrow">EXPORT TERBATAS</p><h2>Unduh ringkasan operasional</h2><p>File hanya memuat metrik agregat dalam scope assignment aktif.</p></div><div><a className="secondary-button" href="/staff/exports/csv">CSV</a><a className="primary-button" href="/staff/exports/xlsx">XLSX</a></div></div>}
            {viewer && <ReadOnlyBanner>Dashboard pimpinan hanya menampilkan agregat sesuai assignment. Jawaban tracer, CV privat, credential, kontak, dan dokumen lamaran tetap tertutup.</ReadOnlyBanner>}
            {admin && <div className="operational-links"><Link href="/admin/tracer">Kelola periode tracer →</Link><Link href="/admin/jobs/import">Preview impor lowongan →</Link></div>}
        </section>
    </main></>;
}
