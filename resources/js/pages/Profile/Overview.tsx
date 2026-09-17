import { Head, Link, router, usePage } from '@inertiajs/react';
import AppHeader from '../../components/AppHeader';
import { candidateNav } from '../../components/CandidateNav';

type Profile = {
    professional_name: string | null;
    headline: string | null;
    professional_email: string | null;
    whatsapp: string | null;
    city: string | null;
    has_photo: boolean;
    open_to_work: boolean;
    profile_visibility: 'private' | 'searchable';
    last_confirmed_at: string | null;
};

type Progress = { percent: number; completed: number; total: number; is_gate: false };
type Section = { key: string; label: string; count: number; empty: string };

export default function Overview({ profile, progress, sections }: { profile: Profile; progress: Progress; sections: Section[] }) {
    const page = usePage<{ flash?: { success?: string } }>();

    return <><Head title="Profil Profesional" /><main className="profile-page app-surface">
        <AppHeader context="PROFIL PROFESIONAL" nav={candidateNav('profile')} logout />
        <section className="profile-shell">
            {page.props.flash?.success && <div className="alert success">{page.props.flash.success}</div>}
            <div className="profile-hero-card"><div className="profile-photo">{profile.has_photo ? <img src="/profile/photo" alt="Foto profil privat" /> : <span>{profile.professional_name?.slice(0, 2).toUpperCase() || 'SK'}</span>}</div><div><p className="eyebrow">PROFIL KANONIS</p><h1 className="profile-name">{profile.professional_name || 'Profil profesional Anda'}</h1><p className="profile-headline">{profile.headline || 'Tambahkan headline agar tujuan karier lebih mudah dipahami.'}</p><div className="profile-meta"><span>⌖ {profile.city || 'Lokasi belum diisi'}</span><span>◉ {profile.open_to_work ? 'Terbuka untuk peluang' : 'Status peluang privat'}</span><span>◆ {profile.profile_visibility === 'searchable' ? 'Diizinkan untuk pencarian mendatang' : 'Profil privat'}</span></div></div><Link className="secondary-button" href="/profile/edit">Edit profil</Link></div>
            <div className="progress-card"><div><p className="eyebrow">PANDUAN KELENGKAPAN</p><strong>{progress.percent}%</strong><p>{progress.completed} dari {progress.total} panduan terisi. Kelengkapan tidak menjadi syarat login atau akses.</p></div><div className="progress-track"><span style={{ width: `${progress.percent}%` }} /></div><button className="text-button" onClick={() => router.post('/profile/confirm')}>Masih sesuai</button></div>
            <div className="section-heading profile-section-heading"><div><p>DATA PROFESIONAL</p><h2>Bangun profil per bagian.</h2></div><Link className="primary-button" href="/cv">Susun CV profesional</Link></div>
            <div className="profile-section-grid">{sections.map((section, index) => <Link className="profile-section-card" href={`/profile/sections/${section.key}`} key={section.key}><span>{String(index + 1).padStart(2, '0')}</span><div><h3>{section.label}</h3><p>{section.count > 0 ? `${section.count} data tersimpan` : section.empty}</p></div><b>Edit →</b></Link>)}</div>
            <div className="privacy-note"><strong>Data Anda tetap di Karir.</strong><p>Email profesional dan WhatsApp terpisah dari akun login Core. Perubahan di sini tidak mengubah profil Core.</p></div>
        </section>
    </main></>;
}
