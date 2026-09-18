import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { CompanyHeader, StaffHeader } from '../../components/RoleHeader';
import { EmptyState } from '../../components/Ui';

type Card = { reference:string; professional_name:string; photo_url:string|null; headline:string|null; city:string|null; education:string; skills:string[]; experience:string[]; badges:string[]; open_to_work:boolean; last_confirmed_at:string|null; matched:string[]; shortlisted:boolean };
type Filters = Record<string, string|number|boolean|null|undefined>;

const textFilters = [
    ['skill', 'Keterampilan'], ['sector', 'Sektor / domain'], ['certification', 'Sertifikasi'],
    ['event_topic', 'Topik event'], ['experience_type', 'Jenis pengalaman'],
    ['education_program', 'Program studi'], ['education_level', 'Jenjang pendidikan'],
    ['graduation_year', 'Tahun lulus'], ['city', 'Kota domisili'],
    ['preferred_location', 'Lokasi pilihan'], ['availability_date', 'Tanggal tersedia'],
] as const;

export default function Search({ audience, heading, scope, filters, results, accessUnavailable = false }:{ audience:'company'|'internal'; heading:string; scope:string; filters:Filters; results:Card[]; accessUnavailable?:boolean }) {
    const [drawer, setDrawer] = useState(false);
    const [state, setState] = useState({
        q: String(filters.q || ''), skill: String(filters.skill || ''), sector: String(filters.sector || ''),
        certification: String(filters.certification || ''), event_topic: String(filters.event_topic || ''),
        experience_type: String(filters.experience_type || ''), education_program: String(filters.education_program || ''),
        education_level: String(filters.education_level || ''), graduation_year: String(filters.graduation_year || ''),
        city: String(filters.city || ''), preferred_location: String(filters.preferred_location || ''),
        availability_date: String(filters.availability_date || ''), open_to_work: filters.open_to_work ? '1' : '',
        willing_to_relocate: filters.willing_to_relocate ? '1' : '',
    });
    const base = audience === 'company' ? '/company/talent' : '/internal/talent';
    const submit = () => router.get(base, state, { preserveState: true });

    return <><Head title="Talent Search"/><main className={'talent-page app-surface '+audience}>
        {audience === 'company' ? <CompanyHeader context="TALENT SEARCH" active="talent" /> : <StaffHeader context="DIREKTORI INTERNAL" active="talent" />}
        <section className="talent-hero">
            <div className="talent-hero-copy"><p className="eyebrow">{accessUnavailable ? 'AKSES DIREKTORI' : 'PENCARIAN TERSTRUKTUR'}</p><h1>{heading}</h1><p>{scope}</p></div>
            {!accessUnavailable && <div className="talent-searchbar"><input aria-label="Cari talenta" value={state.q} onChange={e => setState({...state, q:e.target.value})} placeholder="CPOB, QA/QC, rumah sakit, Halal, Regulatory…" onKeyDown={e => e.key === 'Enter' && submit()}/><button className="primary-button" onClick={submit}>Cari talenta</button><button className="secondary-button filter-toggle" onClick={() => setDrawer(!drawer)}>Filter</button></div>}
            <div className="talent-privacy-line"><span>✓</span> Kontak, Core ID, tracer, dan dokumen privat tidak ditampilkan.</div>
        </section>
        {accessUnavailable ? <section className="talent-access-empty"><span>!</span><div><h2>Lingkup alumni belum ditentukan</h2><p>Administrator SAFA KARIR perlu menautkan akun Anda ke program studi atau fakultas. Setelah itu, menu ini otomatis menampilkan kandidat yang memberi izin internal.</p><Link className="secondary-button" href="/staff">Kembali ke ringkasan</Link></div></section> :
        <section className="talent-layout"><aside className={drawer ? 'filter-panel open' : 'filter-panel'}><div><h2>Filter kandidat</h2><button className="filter-close" onClick={() => setDrawer(false)}>×</button></div>
            {textFilters.map(([key, label]) => <label key={key}>{label}<input type={key === 'availability_date' ? 'date' : key === 'graduation_year' ? 'number' : 'text'} value={state[key]} onChange={e => setState({...state, [key]:e.target.value})}/></label>)}
            <label className="check-row"><input type="checkbox" checked={state.open_to_work === '1'} onChange={e => setState({...state, open_to_work:e.target.checked ? '1' : ''})}/> Terbuka untuk peluang</label>
            <label className="check-row"><input type="checkbox" checked={state.willing_to_relocate === '1'} onChange={e => setState({...state, willing_to_relocate:e.target.checked ? '1' : ''})}/> Bersedia relokasi</label>
            <button className="primary-button" onClick={submit}>Terapkan filter</button><button className="text-button" onClick={() => router.get(base)}>Reset</button>
        </aside><div className="talent-results"><div className="results-heading"><strong>{results.length} kandidat</strong><span>Maksimal 50 hasil · tanpa bulk export</span></div>
            {results.length === 0 ? <EmptyState icon="⌕" title="Belum ada kandidat yang cocok" description="Ubah kata kunci atau filter. Kandidat hanya muncul setelah memberi izin."/> : results.map(r => <article className="talent-card" key={r.reference}>
                <div className="talent-avatar">{r.photo_url ? <img src={r.photo_url} alt={`Foto ${r.professional_name}`} loading="lazy"/> : r.professional_name.split(' ').map(x => x[0]).slice(0, 2).join('')}</div>
                <div className="talent-card-body"><div className="talent-card-title"><div><small>PROFIL TALENTA</small><h2>{r.professional_name}</h2><p>{r.headline || 'Profesional Farmasi'}</p></div>{r.open_to_work && <span className="open-badge">Terbuka</span>}</div>
                    <div className="talent-meta"><span>⌖ {r.city || 'Lokasi belum dicantumkan'}</span><span>⌁ {r.education || 'Pendidikan Farmasi'}</span></div>
                    <div className="talent-tags">{r.skills.map(x => <span key={x}>{x}</span>)}{r.badges.map(x => <span className="credential" key={x}>{x}</span>)}</div>
                    {r.matched.length > 0 && <p className="match-reason"><strong>Cocok dengan:</strong> {r.matched.join(', ')}</p>}
                    <div className="talent-card-actions"><Link className="primary-button" href={base+'/'+r.reference}>Lihat Profil Profesional</Link>{audience === 'company' && <button className="secondary-button" onClick={() => r.shortlisted ? router.delete(base+'/'+r.reference+'/shortlist') : router.post(base+'/'+r.reference+'/shortlist')}>{r.shortlisted ? 'Tersimpan' : 'Simpan Kandidat'}</button>}</div>
                </div>
            </article>)}
        </div></section>}
    </main></>;
}
