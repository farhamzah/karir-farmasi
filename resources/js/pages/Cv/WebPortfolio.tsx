import { useEffect, useMemo, useState } from 'react';
import type { CvItem, CvPaperData } from './CvPaper';

type Actions = { pdfUrl?: string | null; onShare?: () => void; onPrint?: () => void };
type LinkItem = { href: string; label: string; icon: string };

const labels: Record<string, string> = {
    institution_name: 'Institusi', program_name: 'Program', degree: 'Gelar', organization: 'Organisasi', type: 'Jenis',
    location: 'Lokasi', category: 'Fokus', level: 'Level', start_date: 'Mulai', end_date: 'Selesai', start_year: 'Mulai',
    end_year: 'Selesai', status: 'Status', issuer: 'Penerbit', issue_date: 'Terbit', expiry_date: 'Berlaku hingga',
    credential_id: 'ID kredensial', role: 'Peran', proficiency: 'Kemahiran', publication_name: 'Media', published_on: 'Terbit',
    doi: 'DOI', event_type: 'Jenis', organizer: 'Penyelenggara', date: 'Tanggal', topics: 'Topik', certificate_number: 'Nomor sertifikat',
    target_roles: 'Bidang diminati', employment_types: 'Jenis pekerjaan', preferred_locations: 'Lokasi harapan',
    willing_to_relocate: 'Relokasi', availability_date: 'Siap mulai',
};
const primaryFields: Record<string, string[]> = {
    education: ['program_name', 'degree'], experience: ['title'], skills: ['name'], certifications: ['title'],
    organizations: ['role'], projects: ['title'], publications: ['title'], languages: ['language'],
    preferences: ['target_roles'], events: ['title'], event_certificates: ['title'], summary: [],
};
const sectionIcons: Record<string, string> = { summary: '●', education: '◆', experience: '▣', skills: '▥', certifications: '✦', events: '◉', event_certificates: '✓', organizations: '♟', projects: '▰', publications: '¶', languages: '◎', preferences: '⌖' };
const navigationLabels: Record<string, string> = { summary: 'Tentang', education: 'Pendidikan', experience: 'Pengalaman', skills: 'Keahlian', certifications: 'Sertifikat', event_certificates: 'Sertifikat', events: 'Event', organizations: 'Organisasi', projects: 'Proyek', publications: 'Publikasi', languages: 'Bahasa', preferences: 'Kontak' };

function itemTitle(section: string, item: CvItem): string {
    return (primaryFields[section] ?? ['title', 'name']).map(key => item[key]).filter(Boolean).join(' · ');
}
function itemLinks(item: CvItem): LinkItem[] {
    const title = String(item.title ?? '').toLowerCase();
    const links: LinkItem[] = [];
    if (typeof item.credential_url === 'string') links.push({ href: item.credential_url, label: title.includes('cpob') ? 'Lihat Sertifikat CPOB' : title.includes('halal') ? 'Lihat Sertifikat Halal' : 'Lihat Sertifikat', icon: '✓' });
    if (typeof item.project_url === 'string') links.push({ href: item.project_url, label: 'Lihat Proyek', icon: '↗' });
    if (typeof item.url === 'string') links.push({ href: item.url, label: item.url.includes('scholar.google') ? 'Google Scholar' : item.url.includes('orcid') ? 'ORCID' : 'Lihat Publikasi', icon: '↗' });
    return links;
}
function PortfolioItem({ sectionKey, item }: { sectionKey: string; item: CvItem }) {
    const primary = primaryFields[sectionKey] ?? ['title', 'name'];
    const metadata = Object.entries(item).filter(([key, value]) => !primary.includes(key) && !['description', 'credential_url', 'project_url', 'url'].includes(key) && value !== null && value !== '' && value !== false && typeof value !== 'boolean');
    const links = itemLinks(item);
    return <article className="web-portfolio-card"><span className="web-card-icon" aria-hidden="true">{sectionIcons[sectionKey] ?? '•'}</span><div>
        {itemTitle(sectionKey, item) && <h3>{itemTitle(sectionKey, item)}</h3>}
        {metadata.length > 0 && <dl>{metadata.map(([key, value]) => <div key={key}><dt>{labels[key] ?? key.replaceAll('_', ' ')}</dt><dd>{String(value)}</dd></div>)}</dl>}
        {typeof item.description === 'string' && <p>{item.description}</p>}
        {links.length > 0 && <div className="web-card-links">{links.map(link => <a key={link.href} href={link.href} target="_blank" rel="noreferrer"><i>{link.icon}</i>{link.label}</a>)}</div>}
    </div></article>;
}

export default function WebPortfolio({ cv, photoUrl = '/profile/photo', actions }: { cv: CvPaperData; photoUrl?: string; actions?: Actions }) {
    const sections = useMemo(() => new Map(cv.sections.map(section => [section.key, section])), [cv.sections]);
    const visibleSections = cv.sections.filter(section => section.items.length > 0);
    const [active, setActive] = useState(visibleSections[0]?.key ?? 'summary');
    useEffect(() => {
        const elements = visibleSections.map(section => document.getElementById(`web-${section.key}`)).filter(Boolean) as HTMLElement[];
        if (!elements.length || typeof IntersectionObserver === 'undefined') return;
        const observer = new IntersectionObserver(entries => entries.forEach(entry => entry.isIntersecting && setActive(entry.target.id.replace('web-', ''))), { rootMargin: '-25% 0px -60%' });
        elements.forEach(element => observer.observe(element));
        return () => observer.disconnect();
    }, [cv.sections]);

    const summary = String(sections.get('summary')?.items[0]?.description ?? '');
    const skills = sections.get('skills')?.items ?? [];
    const preferences = sections.get('preferences')?.items[0];
    const interests = String(preferences?.target_roles ?? '').split(',').map(value => value.trim()).filter(Boolean);
    const publications = sections.get('publications')?.items ?? [];
    const scholar = publications.find(item => typeof item.url === 'string' && item.url.includes('scholar.google'));
    const orcid = publications.find(item => typeof item.url === 'string' && item.url.includes('orcid'));
    const professionalLinks: LinkItem[] = [
        cv.linkedin_url ? { href: cv.linkedin_url, label: 'LinkedIn', icon: 'in' } : null,
        scholar ? { href: String(scholar.url), label: 'Google Scholar', icon: '◆' } : null,
        orcid ? { href: String(orcid.url), label: 'ORCID', icon: 'iD' } : null,
        cv.portfolio_url ? { href: cv.portfolio_url, label: 'Portofolio', icon: '▰' } : null,
    ].filter((link): link is LinkItem => Boolean(link));
    const contactHref = cv.email ? `mailto:${cv.email}` : cv.whatsapp ? `https://wa.me/${cv.whatsapp.replace(/\D/g, '')}` : null;
    const navigationSections = visibleSections.filter(section => !['preferences'].includes(section.key));
    const groupedSections = visibleSections.filter(section => !['summary', 'skills', 'preferences', 'languages'].includes(section.key));

    return <article className="web-portfolio" data-template-key="cv-09">
        <nav className="web-portfolio-nav print-hidden" aria-label="Navigasi portfolio"><a className="web-portfolio-brand" href="#web-home"><span>SK</span><b>SAFA KARIR<small>ALUMNI FARMASI</small></b></a><div>{navigationSections.map(section => <a className={active === section.key ? 'active' : ''} key={section.key} href={`#web-${section.key}`}>{navigationLabels[section.key] ?? section.title}</a>)}{contactHref && <a href="#web-contact">Kontak</a>}</div></nav>
        <header className="web-portfolio-hero" id="web-home">
            <div className="web-portfolio-photo">{cv.has_photo ? <img src={photoUrl} alt={`Foto ${cv.professional_name}`} loading="eager" /> : <span>{cv.professional_name.split(/\s+/).slice(0, 2).map(part => part[0]).join('')}</span>}</div>
            <div className="web-portfolio-identity"><p className="web-eyebrow">ALUMNI FARMASI · BERKARYA UNTUK MASYARAKAT</p><h1>{cv.professional_name}</h1>{cv.headline && <h2>{cv.headline}</h2>}{summary && <p className="web-hero-summary">{summary}</p>}
                <div className="web-status-row">{cv.open_to_work && <span className="web-open-badge">● Open to Work</span>}{cv.city && <span>⌖ {cv.city}</span>}</div>
                <div className="web-hero-tags">{[...interests, ...skills.slice(0, 4).map(item => String(item.name ?? ''))].filter(Boolean).slice(0, 7).map(tag => <span key={tag}>{tag}</span>)}</div>
                <div className="web-hero-actions print-hidden">{actions?.pdfUrl && <a className="primary" href={actions.pdfUrl}>↓ Unduh CV</a>}{actions?.onShare && <button onClick={actions.onShare}>⌯ Bagikan</button>}{contactHref && <a href={contactHref}>✦ Hubungi Saya</a>}</div>
                {professionalLinks.length > 0 && <div className="web-professional-links">{professionalLinks.map(link => <a href={link.href} key={link.href} target="_blank" rel="noreferrer"><i>{link.icon}</i><span>{link.label}</span></a>)}</div>}
            </div>
        </header>
        <div className="web-quick-nav print-hidden">{visibleSections.filter(section => ['education', 'experience', 'certifications', 'events', 'projects'].includes(section.key)).map(section => <a href={`#web-${section.key}`} key={section.key}><i>{sectionIcons[section.key]}</i><b>{navigationLabels[section.key]}</b><small>{section.title}</small></a>)}{contactHref && <a href="#web-contact"><i>◎</i><b>Kontak</b><small>Terhubung dengan saya</small></a>}</div>
        <main className="web-portfolio-content">
            {(summary || skills.length > 0 || cv.city || contactHref) && <section className="web-about" id="web-summary"><div className="web-about-copy"><div className="web-section-heading"><i>●</i><span><small>PROFIL PROFESIONAL</small><h2>Tentang Saya</h2></span></div>{summary && <p>{summary}</p>}{interests.length > 0 && <div className="web-interest-tags">{interests.map(tag => <span key={tag}>{tag}</span>)}</div>}</div>
                {skills.length > 0 && <div className="web-featured-skills" id="web-skills"><div className="web-section-heading"><i>▥</i><span><small>KOMPETENSI</small><h2>Keahlian Utama</h2></span></div><div>{skills.slice(0, 8).map((item, index) => <span key={index}><b>{String(item.name ?? '')}</b>{item.level && <small>{String(item.level)}</small>}</span>)}</div></div>}
                <aside className="web-contact-card" id="web-contact"><div className="web-section-heading"><i>◎</i><span><small>KONTAK PROFESIONAL</small><h2>Hubungi Saya</h2></span></div>{cv.city && <p>⌖ {cv.city}</p>}{cv.email && <a href={`mailto:${cv.email}`}>✉ {cv.email}</a>}{cv.whatsapp && <a href={`https://wa.me/${cv.whatsapp.replace(/\D/g, '')}`}>● {cv.whatsapp}</a>}{contactHref && <a className="contact-button print-hidden" href={contactHref}>Hubungi Saya</a>}</aside>
            </section>}
            <div className="web-section-grid">{groupedSections.map(section => <section className={`web-portfolio-section web-section-${section.key}`} id={`web-${section.key}`} key={section.key}><div className="web-section-heading"><i>{sectionIcons[section.key] ?? '•'}</i><span><small>{navigationLabels[section.key] ?? 'PORTFOLIO'}</small><h2>{section.title}</h2></span></div><div className="web-portfolio-items">{section.items.map((item, index) => <PortfolioItem sectionKey={section.key} item={item} key={index} />)}</div></section>)}</div>
            {sections.get('languages') && <section className="web-language-section" id="web-languages"><div className="web-section-heading"><i>◎</i><span><small>KOMUNIKASI</small><h2>{sections.get('languages')!.title}</h2></span></div><div>{sections.get('languages')!.items.map((item, index) => <span key={index}><b>{String(item.language ?? '')}</b><small>{String(item.proficiency ?? '')}</small></span>)}</div></section>}
        </main>
        <footer className="web-portfolio-footer"><b>{cv.professional_name}</b>{actions?.onPrint && <button className="print-hidden" onClick={actions.onPrint}>Cetak portfolio</button>}</footer>
    </article>;
}
