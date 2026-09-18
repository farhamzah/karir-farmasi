import { Head, Link } from '@inertiajs/react';
import { CompanyHeader, StaffHeader } from '../../components/RoleHeader';

type Item = Record<string, unknown>;
type Profile = { professional_name:string; photo_url:string|null; headline:string|null; summary:string|null; city:string|null; open_to_work:boolean; educations:Item[]; experiences:Item[]; skills:string[]; certifications:Item[]; events:Item[]; projects:Item[]; publications:Item[]; languages:Item[]; preferences:Item|null; last_confirmed_at:string|null };
const val = (value: unknown) => value == null ? '' : String(value);

export default function TalentProfile({ audience, profile }:{ audience:'company'|'internal'; profile:Profile }) {
    const base = audience === 'company' ? '/company/talent' : '/internal/talent';
    const initials = profile.professional_name.split(' ').map((part) => part[0]).slice(0, 2).join('');

    return <><Head title={profile.professional_name}/><main className="talent-page talent-detail app-surface">
        {audience === 'company' ? <CompanyHeader context="PROFIL TALENTA" active="talent"/> : <StaffHeader context="DIREKTORI INTERNAL" active="talent"/>}
        <section className="talent-profile-hero"><div className="talent-avatar large">{profile.photo_url ? <img src={profile.photo_url} alt={`Foto ${profile.professional_name}`}/> : initials}</div><div><p className="eyebrow">PROFIL PROFESIONAL</p><h1>{profile.professional_name}</h1><h2>{profile.headline || 'Profesional Farmasi'}</h2><p>{profile.summary}</p><div className="talent-meta"><span>⌖ {profile.city || 'Lokasi umum belum dicantumkan'}</span>{profile.open_to_work && <span className="open-badge">Terbuka untuk peluang</span>}</div></div></section>
        <section className="talent-profile-grid"><article><h2>Keterampilan</h2><div className="talent-tags">{profile.skills.map(item => <span key={item}>{item}</span>)}</div></article><article><h2>Pendidikan</h2>{profile.educations.map((item,index) => <div className="timeline-item" key={index}><strong>{val(item.degree)} {val(item.program_name)}</strong><p>{val(item.institution_name)} · {val(item.end_year)}</p></div>)}</article><article className="wide"><h2>Pengalaman</h2>{profile.experiences.map((item,index) => <div className="timeline-item" key={index}><strong>{val(item.title)}</strong><p>{val(item.organization)} · {val(item.location)}</p><p>{val(item.description)}</p></div>)}</article><article><h2>Sertifikasi</h2>{profile.certifications.map((item,index) => <div className="mini-record" key={index}><strong>{val(item.title)}</strong><p>{val(item.issuer)}</p></div>)}</article><article><h2>Event &amp; Pelatihan</h2>{profile.events.map((item,index) => <div className="mini-record" key={index}><strong>{val(item.title)}</strong><p>{val(item.role)}</p></div>)}</article></section>
        <footer className="privacy-footer"><strong>Ringkasan berbasis izin kandidat</strong><p>Kontak, ID Core, tracer, credential privat, dan attachment tidak ditampilkan.</p><Link href={base} className="secondary-button">Kembali ke hasil</Link></footer>
    </main></>;
}
