import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppHeader from '../../components/AppHeader';
import { candidateNav } from '../../components/CandidateNav';
import { StaffHeader } from '../../components/RoleHeader';

type Alumni = { reference: string; name: string; nim: string; graduation_year: number | null; photo_url: string | null };
type Props = {
    alumni: Alumni[];
    audience: 'alumni' | 'staff';
    filters: { q: string; graduation_year: number | null };
    graduationYears: number[];
    total: number;
    unrestricted: boolean;
};

function initials(name: string) {
    return name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
}

export default function Index({ alumni, audience, filters, graduationYears, total, unrestricted }: Props) {
    const [query, setQuery] = useState(filters.q);
    const [year, setYear] = useState(filters.graduation_year ? String(filters.graduation_year) : '');
    const search = () => router.get('/alumni', { q: query || undefined, graduation_year: year || undefined }, { preserveState: true });

    return <><Head title="Direktori Alumni" /><main className="alumni-directory-page app-surface">
        {audience === 'alumni'
            ? <AppHeader context="DIREKTORI ALUMNI" nav={candidateNav('alumni')} actions={<span className="status-dot">Sesama alumni</span>} logout />
            : <StaffHeader context="DAFTAR ALUMNI" active="alumni" />}
        <section className="alumni-directory-shell">
            <header className="alumni-directory-hero">
                <div><p className="eyebrow">JEJARING FARMASI UBP</p><h1>Wajah alumni,<br /><em>lintas angkatan.</em></h1><p className="alumni-directory-lead">Daftar identitas ringan untuk mengenali alumni. Profil profesional, kontak, dokumen, dan tracer tidak ditampilkan di halaman ini.</p></div>
                <div className="alumni-directory-stat"><strong>{total}</strong><span>{unrestricted ? 'alumni terdaftar' : 'alumni memberi izin tampil'}</span><p>Urutan mengikuti NIM agar angkatan mudah dikenali.</p></div>
            </header>
            <section className="alumni-directory-toolbar" aria-label="Filter direktori alumni">
                <label><span>Cari alumni</span><input value={query} onChange={(event) => setQuery(event.target.value)} onKeyDown={(event) => event.key === 'Enter' && search()} placeholder="Nama atau NIM" /></label>
                <label><span>Tahun lulus</span><select value={year} onChange={(event) => setYear(event.target.value)}><option value="">Semua tahun</option>{graduationYears.map((item) => <option value={item} key={item}>{item}</option>)}</select></label>
                <button className="primary-button" type="button" onClick={search}>Tampilkan</button>
                {(filters.q || filters.graduation_year) && <button className="text-button" type="button" onClick={() => router.get('/alumni')}>Reset</button>}
                <p><strong>{alumni.length}</strong> hasil ditampilkan</p>
            </section>
            {alumni.length === 0 ? <section className="alumni-directory-empty"><strong>Belum ada alumni yang cocok.</strong><p>{unrestricted ? 'Ubah pencarian atau tahun lulus. Data muncul setelah profil memiliki NIM.' : 'Ubah pencarian atau tahun lulus. Alumni hanya muncul setelah memberi izin tampil.'}</p></section> :
                <section className="alumni-directory-grid" aria-label="Daftar alumni">
                    {alumni.map((person) => <article className="alumni-directory-card" key={person.reference}>
                        <div className="alumni-directory-photo">{person.photo_url ? <img src={person.photo_url} alt={`Foto ${person.name}`} loading="lazy" /> : <span className="alumni-directory-avatar" aria-hidden="true">{initials(person.name)}</span>}<i aria-hidden="true" /></div>
                        <div className="alumni-directory-identity"><span>ALUMNI FARMASI UBP</span><h2>{person.name}</h2><dl><div><dt>NIM</dt><dd>{person.nim}</dd></div><div><dt>Lulus</dt><dd>{person.graduation_year ?? 'Belum dicantumkan'}</dd></div></dl></div>
                    </article>)}
                </section>}
            <footer className="alumni-directory-privacy"><span>✓</span><p><strong>Data secukupnya.</strong> Halaman ini tidak membuka email, WhatsApp, CV, riwayat lamaran, tracer, atau dokumen privat.</p></footer>
        </section>
    </main></>;
}
