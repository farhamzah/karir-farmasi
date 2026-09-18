import { Head } from '@inertiajs/react';
import AppHeader from '../../components/AppHeader';
import { candidateNav } from '../../components/CandidateNav';

type Alumni = {
    reference: string;
    name: string;
    nim: string;
    graduation_year: number | null;
    photo_url: string | null;
};

function initials(name: string) {
    return name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
}

export default function Index({ alumni }: { alumni: Alumni[] }) {
    return <><Head title="Direktori Alumni" /><main className="alumni-directory-page app-surface">
        <AppHeader context="DIREKTORI ALUMNI" nav={candidateNav('alumni')} actions={<span className="status-dot">Khusus alumni</span>} logout />
        <section className="alumni-directory-shell">
            <header className="alumni-directory-hero">
                <div><p className="eyebrow">TEMUKAN ANGKATAN</p><h1>Kenali sesama<br /><em>alumni Farmasi.</em></h1></div>
                <div className="alumni-directory-intro"><strong>{alumni.length} alumni ditampilkan</strong><p>Daftar diurutkan berdasarkan NIM agar teman satu angkatan mudah ditemukan.</p><span>Hanya foto, nama, NIM, dan tahun lulus yang terlihat.</span></div>
            </header>
            {alumni.length === 0 ? <section className="alumni-directory-empty"><strong>Direktori belum berisi alumni.</strong><p>Alumni akan muncul setelah pendaftaran disetujui dan izin tampil diaktifkan.</p></section> :
                <section className="alumni-directory-grid" aria-label="Daftar alumni">
                    {alumni.map((person) => <article className="alumni-directory-card" key={person.reference}>
                        {person.photo_url ? <img src={person.photo_url} alt={`Foto ${person.name}`} loading="lazy" /> : <span className="alumni-directory-avatar" aria-hidden="true">{initials(person.name)}</span>}
                        <div><h2>{person.name}</h2><dl><div><dt>NIM</dt><dd>{person.nim}</dd></div><div><dt>Tahun lulus</dt><dd>{person.graduation_year ?? '—'}</dd></div></dl></div>
                    </article>)}
                </section>}
        </section>
    </main></>;
}
