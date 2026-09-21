import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { CompanyHeader, StaffHeader } from '../../components/RoleHeader';

type Item = Record<string, unknown>;
type Profile = {
    professional_name: string; photo_url: string | null; headline: string | null; summary: string | null;
    city: string | null; open_to_work: boolean; educations: Item[]; experiences: Item[]; skills: string[];
    certifications: Item[]; events: Item[]; organizations: Item[]; projects: Item[]; publications: Item[];
    languages: Item[]; preferences: Item | null; last_confirmed_at: string | null;
};
const value = (input: unknown): string => input == null ? '' : String(input).trim();
const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
function monthYear(input: unknown): string {
    const date = value(input);
    const match = /^(\d{4})-(\d{2})/.exec(date);
    return match ? `${months[Number(match[2]) - 1] ?? ''} ${match[1]}`.trim() : date;
}
function period(start: unknown, end: unknown, ongoing = false): string {
    const first = monthYear(start);
    const last = ongoing ? 'Sekarang' : monthYear(end);
    return first && last ? `${first}–${last}` : first || last;
}
function educationTitle(item: Item): string {
    const program = value(item.program_name);
    const degree = value(item.degree);
    return program && degree && !program.toLocaleLowerCase('id-ID').includes(degree.toLocaleLowerCase('id-ID'))
        ? `${program} · ${degree}` : program || degree;
}
function Section({ id, label, title, count, children }: { id: string; label: string; title: string; count: number; children: ReactNode }) {
    if (!count) return null;
    return <section id={id} className="talent-detail-section">
        <div className="talent-detail-section-heading"><div><span>{label}</span><h2>{title}</h2></div><small>{count} entri</small></div>
        {children}
    </section>;
}

export default function TalentProfile({ audience, profile }: { audience: 'company' | 'internal'; profile: Profile }) {
    const base = audience === 'company' ? '/company/talent' : '/internal/talent';
    const initials = profile.professional_name.split(/\s+/).slice(0, 2).map(part => part[0]).join('').toUpperCase();
    const navigation = [
        ['experience', 'Pengalaman', profile.experiences.length], ['education', 'Pendidikan', profile.educations.length],
        ['skills', 'Keahlian', profile.skills.length], ['certifications', 'Sertifikasi', profile.certifications.length],
        ['projects', 'Proyek', profile.projects.length], ['publications', 'Publikasi', profile.publications.length],
        ['organizations', 'Organisasi', profile.organizations.length],
        ['events', 'Event', profile.events.length], ['languages', 'Bahasa', profile.languages.length],
    ] as const;
    const interests = profile.preferences && Array.isArray(profile.preferences.target_roles)
        ? profile.preferences.target_roles.map(value).filter(Boolean) : [];

    return <><Head title={profile.professional_name} /><main className="talent-page talent-detail app-surface">
        {audience === 'company' ? <CompanyHeader context="PROFIL TALENTA" active="talent" /> : <StaffHeader context="DIREKTORI INTERNAL" active="talent" />}
        <div className="talent-detail-shell">
            <div className="talent-detail-breadcrumb"><Link href={base}>← Kembali ke Direktori Talenta</Link><span>Profil profesional</span></div>
            <header className="talent-detail-hero">
                <div className="talent-detail-portrait">{profile.photo_url ? <img src={profile.photo_url} alt={`Foto ${profile.professional_name}`} /> : <span>{initials}</span>}</div>
                <div className="talent-detail-identity"><p className="eyebrow">ALUMNI FARMASI UBP · PROFIL PROFESIONAL</p><h1>{profile.professional_name}</h1>
                    {profile.headline && <p className="talent-detail-headline">{profile.headline}</p>}
                    <div className="talent-detail-badges">{profile.city && <span>⌖ {profile.city}</span>}{profile.open_to_work && <span className="is-open">● Terbuka untuk peluang</span>}</div>
                    {profile.summary && <p className="talent-detail-summary">{profile.summary}</p>}
                </div>
            </header>
            <nav className="talent-detail-nav" aria-label="Bagian profil">{navigation.filter(([, , count]) => count > 0).map(([id, label]) => <a href={`#talent-${id}`} key={id}>{label}</a>)}</nav>
            <div className="talent-detail-content">
                <div className="talent-detail-main">
                    <Section id="talent-experience" label="01 / JEJAK PROFESIONAL" title="Pengalaman" count={profile.experiences.length}>
                        <div className="talent-detail-list">{profile.experiences.map((item, index) => <article className="talent-detail-record" key={index}>
                            <div className="talent-record-top"><h3>{value(item.title)}</h3>{period(item.start_date, item.end_date, Boolean(item.currently_active)) && <time>{period(item.start_date, item.end_date, Boolean(item.currently_active))}</time>}</div>
                            <p className="talent-record-sub">{[value(item.organization), value(item.location)].filter(Boolean).join(' · ')}</p>
                            {value(item.description) && <p className="talent-record-description">{value(item.description)}</p>}
                        </article>)}</div>
                    </Section>
                    <Section id="talent-education" label="02 / LATAR AKADEMIK" title="Pendidikan" count={profile.educations.length}>
                        <div className="talent-detail-list">{profile.educations.map((item, index) => <article className="talent-detail-record" key={index}>
                            <div className="talent-record-top"><h3>{educationTitle(item)}</h3>{period(item.start_year, item.end_year) && <time>{period(item.start_year, item.end_year)}</time>}</div>
                            <p className="talent-record-sub">{value(item.institution_name)}</p>
                            {value(item.status) === 'studying' && <span className="talent-record-state">Sedang ditempuh</span>}
                        </article>)}</div>
                    </Section>
                    <Section id="talent-projects" label="03 / HASIL KERJA" title="Proyek & karya" count={profile.projects.length}>
                        <div className="talent-detail-list">{profile.projects.map((item, index) => <article className="talent-detail-record" key={index}>
                            <h3>{value(item.title)}</h3>{value(item.category) && <p className="talent-record-sub">{value(item.category)}</p>}
                            {value(item.description) && <p className="talent-record-description">{value(item.description)}</p>}
                        </article>)}</div>
                    </Section>
                    <Section id="talent-publications" label="04 / KONTRIBUSI ILMIAH" title="Publikasi" count={profile.publications.length}>
                        <div className="talent-detail-list">{profile.publications.map((item, index) => <article className="talent-detail-record" key={index}>
                            <div className="talent-record-top"><h3>{value(item.title)}</h3>{monthYear(item.published_on) && <time>{monthYear(item.published_on)}</time>}</div>
                            <p className="talent-record-sub">{value(item.publication_name)}</p>
                        </article>)}</div>
                    </Section>
                    <Section id="talent-organizations" label="05 / AKTIVITAS" title="Organisasi" count={profile.organizations.length}>
                        <div className="talent-detail-list">{profile.organizations.map((item, index) => <article className="talent-detail-record" key={index}>
                            <div className="talent-record-top"><h3>{value(item.role)}</h3>{period(item.start_date, item.end_date) && <time>{period(item.start_date, item.end_date)}</time>}</div>
                            <p className="talent-record-sub">{value(item.organization)}</p>
                            {value(item.description) && <p className="talent-record-description">{value(item.description)}</p>}
                        </article>)}</div>
                    </Section>
                </div>
                <aside className="talent-detail-side">
                    <Section id="talent-skills" label="KOMPETENSI" title="Keahlian" count={profile.skills.length}>
                        <div className="talent-detail-chips">{profile.skills.map((skill, index) => <span key={`${skill}-${index}`}>{skill}</span>)}</div>
                    </Section>
                    <Section id="talent-certifications" label="KREDENSIAL" title="Sertifikasi" count={profile.certifications.length}>
                        <div className="talent-detail-list">{profile.certifications.map((item, index) => <article className="talent-detail-record" key={index}>
                            <div className="talent-record-top"><h3>{value(item.title)}</h3>{monthYear(item.issue_date) && <time>{monthYear(item.issue_date)}</time>}</div>
                            <p className="talent-record-sub">{value(item.issuer)}</p>
                        </article>)}</div>
                    </Section>
                    <Section id="talent-events" label="PENGEMBANGAN DIRI" title="Event & pelatihan" count={profile.events.length}>
                        <div className="talent-detail-list">{profile.events.map((item, index) => <article className="talent-detail-record" key={index}>
                            <h3>{value(item.title)}</h3><p className="talent-record-sub">{value(item.role)}</p>
                            {Array.isArray(item.topics) && item.topics.length > 0 && <div className="talent-detail-chips small">{item.topics.map((topic, topicIndex) => <span key={topicIndex}>{value(topic)}</span>)}</div>}
                        </article>)}</div>
                    </Section>
                    <Section id="talent-languages" label="KOMUNIKASI" title="Bahasa" count={profile.languages.length}>
                        <div className="talent-detail-list">{profile.languages.map((item, index) => <div className="talent-language" key={index}><b>{value(item.language)}</b><span>{value(item.proficiency)}</span></div>)}</div>
                    </Section>
                    {interests.length > 0 && <section className="talent-detail-section"><div className="talent-detail-section-heading"><div><span>ARAH KARIER</span><h2>Bidang diminati</h2></div></div><div className="talent-detail-chips">{interests.map((interest: string, index: number) => <span key={index}>{interest}</span>)}</div></section>}
                </aside>
            </div>
            <footer className="talent-detail-footer"><div><strong>Profil berdasarkan izin alumni</strong><p>Informasi kontak dan dokumen privat tidak ditampilkan di Direktori Talenta.</p></div><Link href={base} className="secondary-button">Kembali ke direktori</Link></footer>
        </div>
    </main></>;
}
