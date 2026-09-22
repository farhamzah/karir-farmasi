import { useEffect, useMemo, useState } from 'react';
import type { CvItem, CvPaperData } from './CvPaper';

type Actions = { pdfUrl?: string | null; onShare?: () => void; onPrint?: () => void };
type PortfolioLink = { href: string; label: string; note: string; icon: string };

const labels: Record<string, string> = {
    institution_name: 'Institusi', program_name: 'Program', degree: 'Gelar', gpa: 'IPK', organization: 'Organisasi', type: 'Jenis',
    location: 'Lokasi', category: 'Fokus', level: 'Tingkat', start_date: 'Mulai', end_date: 'Selesai', start_year: 'Mulai',
    end_year: 'Selesai', status: 'Status', issuer: 'Penerbit', issue_date: 'Terbit', expiry_date: 'Berlaku hingga',
    credential_id: 'ID kredensial', role: 'Peran', proficiency: 'Kemahiran', publication_name: 'Media', published_on: 'Terbit',
    doi: 'DOI', event_type: 'Jenis', organizer: 'Penyelenggara', date: 'Tanggal', topics: 'Topik', certificate_number: 'Nomor sertifikat',
};
const primaryFields: Record<string, string[]> = {
    education: ['program_name', 'degree'], experience: ['title'], certifications: ['title'], events: ['title'], event_certificates: ['title'],
    organizations: ['role'], projects: ['title'], publications: ['title'], skills: ['name'], languages: ['language'], preferences: ['target_roles'], summary: [],
};
const sectionNames: Record<string, string> = { summary: 'About', education: 'Education', experience: 'Experience', certifications: 'Certifications', events: 'Events & Seminars', event_certificates: 'Event Certificates', organizations: 'Organizations', projects: 'Portfolio / Projects', publications: 'Publications', skills: 'Skills', languages: 'Languages' };
const sectionIcons: Record<string, string> = { education: '◆', experience: '▣', certifications: '✦', events: '◈', event_certificates: '✓', organizations: '♟', projects: '▰', publications: '¶', skills: '✣', languages: '◎' };

function titleFor(sectionKey: string, item: CvItem): string {
    return (primaryFields[sectionKey] ?? ['title', 'name']).map(key => item[key]).filter(Boolean).join(' · ');
}

function credentialLabel(item: CvItem): string {
    const title = String(item.title ?? '').toLowerCase();
    if (title.includes('ijazah')) return 'Lihat Ijazah';
    if (title.includes('transkrip')) return 'Lihat Transkrip';
    if (title.includes('cpob')) return 'Lihat Sertifikat CPOB';
    if (title.includes('halal')) return 'Lihat Sertifikat Halal';
    return 'Lihat Sertifikat';
}

function linksFor(item: CvItem): PortfolioLink[] {
    const links: PortfolioLink[] = [];
    if (typeof item.credential_url === 'string') links.push({ href: item.credential_url, label: credentialLabel(item), note: 'Dokumen pendukung', icon: '✦' });
    if (typeof item.project_url === 'string') links.push({ href: item.project_url, label: 'Lihat Portofolio', note: 'Karya terpilih', icon: '▰' });
    if (typeof item.url === 'string') links.push({ href: item.url, label: item.url.toLowerCase().includes('scholar.google') ? 'Google Scholar' : 'Lihat Publikasi', note: 'Publikasi profesional', icon: '¶' });
    return links;
}

function EditorialItem({ sectionKey, item }: { sectionKey: string; item: CvItem }) {
    const primary = primaryFields[sectionKey] ?? ['title', 'name'];
    const metadata = Object.entries(item).filter(([key, value]) => !primary.includes(key) && !['description', 'credential_url', 'project_url', 'url'].includes(key) && value !== null && value !== '' && value !== false && typeof value !== 'boolean');
    const links = linksFor(item);
    return <article className="editorial-item"><div className="editorial-item-marker" aria-hidden="true" /><div>
        {titleFor(sectionKey, item) && <h3>{titleFor(sectionKey, item)}</h3>}
        {metadata.length > 0 && <dl>{metadata.map(([key, value]) => <div key={key}><dt>{labels[key] ?? key.replaceAll('_', ' ')}</dt><dd>{String(value)}</dd></div>)}</dl>}
        {typeof item.description === 'string' && <p>{item.description}</p>}
        {links.length > 0 && <div className="editorial-item-links">{links.map(link => <a href={link.href} key={link.href} target="_blank" rel="noreferrer">{link.label}<span>↗</span></a>)}</div>}
    </div></article>;
}

export default function EditorialPortfolio({ cv, photoUrl = '/profile/photo', actions }: { cv: CvPaperData; photoUrl?: string; actions?: Actions }) {
    const sections = useMemo(() => new Map(cv.sections.map(section => [section.key, section])), [cv.sections]);
    const visible = cv.sections.filter(section => section.items.length > 0);
    const summary = String(sections.get('summary')?.items[0]?.description ?? '');
    const skills = sections.get('skills')?.items ?? [];
    const preferences = sections.get('preferences')?.items[0];
    const interests = String(preferences?.target_roles ?? '').split(',').map(value => value.trim()).filter(Boolean);
    const publications = sections.get('publications')?.items ?? [];
    const scholar = publications.find(item => typeof item.url === 'string' && item.url.toLowerCase().includes('scholar.google'));
    const allItemLinks = visible.flatMap(section => section.items.flatMap(linksFor));
    const quickLinks: PortfolioLink[] = [
        cv.linkedin_url ? { href: cv.linkedin_url, label: 'LinkedIn', note: 'Profil profesional', icon: 'in' } : null,
        scholar ? { href: String(scholar.url), label: 'Google Scholar', note: 'Publikasi dan sitasi', icon: '◆' } : null,
        ...allItemLinks,
        cv.portfolio_url ? { href: cv.portfolio_url, label: 'Lihat Portofolio', note: 'Karya terpilih', icon: '▰' } : null,
    ].filter((link): link is PortfolioLink => Boolean(link)).filter((link, index, links) => links.findIndex(candidate => candidate.href === link.href) === index).slice(0, 6);
    const contactHref = cv.email ? `mailto:${cv.email}` : cv.whatsapp ? `https://wa.me/${cv.whatsapp.replace(/\D/g, '')}` : null;
    const [active, setActive] = useState('editorial-home');
    const navigation = [
        ['editorial-home', 'Home'], ['editorial-about', 'About'], ['editorial-education', 'Education'], ['editorial-experience', 'Experience'],
        ['editorial-projects', 'Portfolio'], ['editorial-publications', 'Publications'], ['editorial-contact', 'Contact'],
    ].filter(([id]) => id === 'editorial-home' || (id === 'editorial-contact' ? Boolean(contactHref || cv.city) : id === 'editorial-about' ? Boolean(summary) : visible.some(section => `editorial-${section.key}` === id)));

    useEffect(() => {
        const elements = navigation.map(([id]) => document.getElementById(id)).filter(Boolean) as HTMLElement[];
        if (!elements.length || typeof IntersectionObserver === 'undefined') return;
        const observer = new IntersectionObserver(entries => entries.forEach(entry => entry.isIntersecting && setActive(entry.target.id)), { rootMargin: '-28% 0px -62%' });
        elements.forEach(element => observer.observe(element));
        return () => observer.disconnect();
    }, [cv.sections]);

    const standardSections = visible.filter(section => !['summary', 'skills', 'languages', 'preferences'].includes(section.key));
    const initials = cv.professional_name.split(/\s+/).slice(0, 2).map(part => part[0]).join('');

    return <article className="editorial-portfolio" data-template-key="cv-10">
        <nav className="editorial-nav print-hidden" aria-label="Navigasi portfolio editorial"><a className="editorial-brand" href="#editorial-home"><i>SK</i><span><b>SAFA KARIR</b><small>Pharmacy Alumni Network</small></span></a><div className="editorial-nav-links">{navigation.map(([id, label]) => <a className={active === id ? 'active' : ''} href={`#${id}`} key={id}>{label}</a>)}</div>{contactHref && <a className="editorial-nav-cta" href={contactHref}>Hubungi Saya <span>→</span></a>}</nav>

        <header className="editorial-hero" id="editorial-home">
            <div className="editorial-hero-copy"><p className="editorial-eyebrow">PHARMACY ALUMNI PORTFOLIO</p><h1>{cv.professional_name}</h1>{cv.headline && <h2>{cv.headline}</h2>}{summary && <p className="editorial-summary">{summary}</p>}
                <div className="editorial-tags">{[...interests, ...skills.map(item => String(item.name ?? ''))].filter(Boolean).slice(0, 4).map(tag => <span key={tag}>{tag}</span>)}</div>
                <div className="editorial-actions print-hidden">{contactHref && <a className="primary" href={contactHref}>Hubungi Saya <span>→</span></a>}{actions?.pdfUrl && <a href={actions.pdfUrl}>↓ Download CV</a>}{actions?.onShare && <button onClick={actions.onShare}>Bagikan</button>}</div>
            </div>
            <div className="editorial-portrait">{cv.has_photo ? <img src={photoUrl} alt={`Foto ${cv.professional_name}`} loading="eager" /> : <span>{initials}</span>}</div>
            <aside className="editorial-highlights">
                {cv.open_to_work && <div><i>●</i><span><b>Tersedia untuk peluang</b><small>Terbuka untuk kesempatan profesional</small></span></div>}
                {interests[0] && <div><i>◇</i><span><b>Bidang utama</b><small>{interests.slice(0, 2).join(' · ')}</small></span></div>}
                {cv.city && <div><i>⌖</i><span><b>Lokasi</b><small>{cv.city}</small></span></div>}
                {contactHref && <div><i>♥</i><span><b>Kontak profesional</b><small>Tersedia melalui email atau WhatsApp</small></span></div>}
            </aside>
        </header>

        {quickLinks.length > 0 && <nav className="editorial-credentials" aria-label="Tautan dan dokumen profesional">{quickLinks.map(link => <a href={link.href} key={link.href} target="_blank" rel="noreferrer"><i>{link.icon}</i><span><b>{link.label}</b><small>{link.note}</small></span><em>↗</em></a>)}</nav>}

        <main className="editorial-content">
            {summary && <section className="editorial-about" id="editorial-about"><header><p>TENTANG SAYA</p><h2>Profil profesional</h2></header><div><p>{summary}</p>{interests.length > 0 && <div>{interests.map(interest => <span key={interest}>{interest}</span>)}</div>}</div></section>}
            <div className="editorial-grid">{standardSections.map(section => <section className={`editorial-section editorial-${section.key}`} id={`editorial-${section.key}`} key={section.key}><header><i>{sectionIcons[section.key] ?? '•'}</i><div><small>{sectionNames[section.key] ?? section.title}</small><h2>{section.title}</h2></div></header><div className="editorial-items">{section.items.map((item, index) => <EditorialItem sectionKey={section.key} item={item} key={index} />)}</div></section>)}</div>
            {(skills.length > 0 || sections.get('languages')) && <section className="editorial-capabilities"><header><p>KOMPETENSI</p><h2>Keahlian &amp; bahasa</h2></header>{skills.length > 0 && <div className="editorial-skill-chips">{skills.map((item, index) => <span key={index}><b>{String(item.name ?? '')}</b>{item.level && <small>{String(item.level)}</small>}</span>)}</div>}{sections.get('languages') && <div className="editorial-language-list">{sections.get('languages')!.items.map((item, index) => <span key={index}><b>{String(item.language ?? '')}</b><small>{String(item.proficiency ?? '')}</small></span>)}</div>}</section>}
            {(cv.city || cv.email || cv.whatsapp || cv.linkedin_url) && <section className="editorial-contact" id="editorial-contact"><div><p>CONTACT</p><h2>Mari terhubung secara profesional.</h2></div><address>{cv.city && <span>⌖ {cv.city}</span>}{cv.email && <a href={`mailto:${cv.email}`}>@ {cv.email}</a>}{cv.whatsapp && <a href={`https://wa.me/${cv.whatsapp.replace(/\D/g, '')}`}>◉ {cv.whatsapp}</a>}{cv.linkedin_url && <a href={cv.linkedin_url} target="_blank" rel="noreferrer">in LinkedIn</a>}</address>{contactHref && <a className="editorial-contact-button print-hidden" href={contactHref}>Hubungi Saya →</a>}</section>}
        </main>
        <footer className="editorial-footer"><a className="editorial-brand" href="#editorial-home"><i>SK</i><span><b>SAFA KARIR</b><small>Pharmacy Alumni Network</small></span></a><nav className="print-hidden">{navigation.slice(0, 6).map(([id, label]) => <a href={`#${id}`} key={id}>{label}</a>)}</nav>{actions?.onPrint && <button className="print-hidden" onClick={actions.onPrint}>Cetak portfolio</button>}</footer>
    </article>;
}
