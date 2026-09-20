import { useEffect, useMemo, useState } from 'react';
import type { CvItem, CvPaperData } from './CvPaper';

type Actions = { pdfUrl?: string | null; onShare?: () => void; onPrint?: () => void };
type SmartLink = { href: string; label: string; note: string; icon: string };

const titles: Record<string, string> = {
    education: 'Education', experience: 'Experience', certifications: 'Certifications', events: 'Events & Seminars',
    event_certificates: 'Event Certificates', organizations: 'Organizations', projects: 'Portfolio / Projects',
    publications: 'Publications', skills: 'Skills', languages: 'Languages', preferences: 'Career Interests',
};
const icons: Record<string, string> = {
    education: '◇', experience: '▣', certifications: '✦', events: '▦', event_certificates: '✓',
    organizations: '♙', projects: '▰', publications: '▥', skills: '◫', languages: '◎', preferences: '⌖',
};
const primaryFields: Record<string, string[]> = {
    education: ['program_name', 'degree'], experience: ['title'], certifications: ['title'], events: ['title'],
    event_certificates: ['title'], organizations: ['role'], projects: ['title'], publications: ['title'],
    skills: ['name'], languages: ['language'], preferences: ['target_roles'], summary: [],
};
const metadataLabels: Record<string, string> = {
    institution_name: 'Institution', organization: 'Organization', issuer: 'Issuer', organizer: 'Organizer', location: 'Location',
    start_date: 'From', end_date: 'Until', start_year: 'From', end_year: 'Until', issue_date: 'Issued', published_on: 'Published',
    date: 'Date', role: 'Role', category: 'Category', event_type: 'Type', level: 'Level', proficiency: 'Level', status: 'Status',
    degree: 'Degree', doi: 'DOI', topics: 'Topics', certificate_number: 'Certificate', employment_types: 'Work type',
    preferred_locations: 'Preferred location', availability_date: 'Available', willing_to_relocate: 'Relocation',
};

function itemTitle(section: string, item: CvItem): string {
    return (primaryFields[section] ?? ['title', 'name']).map(key => item[key]).filter(Boolean).join(' · ');
}

function documentLabel(item: CvItem): string {
    const value = String(item.title ?? '').toLowerCase();
    if (value.includes('ijazah')) return 'Lihat Ijazah';
    if (value.includes('transkrip')) return 'Lihat Transkrip';
    if (value.includes('cpob')) return 'Sertifikat CPOB';
    if (value.includes('halal')) return 'Sertifikat Halal';
    return 'Lihat Sertifikat';
}

function itemLinks(item: CvItem): SmartLink[] {
    const links: SmartLink[] = [];
    if (typeof item.credential_url === 'string') links.push({ href: item.credential_url, label: documentLabel(item), note: 'Dokumen terverifikasi', icon: '✦' });
    if (typeof item.project_url === 'string') links.push({ href: item.project_url, label: 'Lihat Portofolio', note: 'Karya pilihan', icon: '▰' });
    if (typeof item.url === 'string') links.push({ href: item.url, label: item.url.toLowerCase().includes('scholar.google') ? 'Google Scholar' : 'Lihat Publikasi', note: 'Karya ilmiah', icon: '▥' });
    return links;
}

function PortfolioItem({ section, item }: { section: string; item: CvItem }) {
    const primary = primaryFields[section] ?? ['title', 'name'];
    const meta = Object.entries(item).filter(([key, value]) => !primary.includes(key)
        && !['description', 'credential_url', 'project_url', 'url'].includes(key)
        && value !== null && value !== '' && value !== false && typeof value !== 'boolean');
    const links = itemLinks(item);
    return <article className="signature-item">
        {itemTitle(section, item) && <h3>{itemTitle(section, item)}</h3>}
        {meta.length > 0 && <dl>{meta.map(([key, value]) => <div key={key}><dt>{metadataLabels[key] ?? key.replaceAll('_', ' ')}</dt><dd>{String(value)}</dd></div>)}</dl>}
        {typeof item.description === 'string' && <p>{item.description}</p>}
        {links.length > 0 && <div className="signature-item-links">{links.map(link => <a href={link.href} key={link.href} target="_blank" rel="noreferrer">{link.label}<span>↗</span></a>)}</div>}
    </article>;
}

export default function SignaturePortfolio({ cv, photoUrl = '/profile/photo', actions }: { cv: CvPaperData; photoUrl?: string; actions?: Actions }) {
    const sectionMap = useMemo(() => new Map(cv.sections.map(section => [section.key, section])), [cv.sections]);
    const summary = String(sectionMap.get('summary')?.items[0]?.description ?? '');
    const skills = sectionMap.get('skills')?.items ?? [];
    const languages = sectionMap.get('languages')?.items ?? [];
    const preferences = sectionMap.get('preferences')?.items[0];
    const interests = String(preferences?.target_roles ?? '').split(',').map(value => value.trim()).filter(Boolean);
    const contentSections = cv.sections.filter(section => section.items.length > 0 && !['summary', 'skills', 'languages', 'preferences'].includes(section.key));
    const publications = sectionMap.get('publications')?.items ?? [];
    const scholar = publications.find(item => typeof item.url === 'string' && item.url.toLowerCase().includes('scholar.google'));
    const quickLinks: SmartLink[] = [
        cv.linkedin_url ? { href: cv.linkedin_url, label: 'LinkedIn', note: 'Profil profesional', icon: 'in' } : null,
        scholar ? { href: String(scholar.url), label: 'Google Scholar', note: 'Publikasi dan sitasi', icon: '◆' } : null,
        ...contentSections.flatMap(section => section.items.flatMap(itemLinks)),
        cv.portfolio_url ? { href: cv.portfolio_url, label: 'Lihat Portofolio', note: 'Karya terpilih', icon: '▰' } : null,
    ].filter((link): link is SmartLink => Boolean(link))
        .filter((link, index, links) => links.findIndex(candidate => candidate.href === link.href) === index).slice(0, 6);
    const contactHref = cv.email ? `mailto:${cv.email}` : cv.whatsapp ? `https://wa.me/${cv.whatsapp.replace(/\D/g, '')}` : null;
    const [active, setActive] = useState('signature-home');
    const nav = [
        ['signature-home', 'Home'], ['signature-about', 'About'], ['signature-education', 'Education'],
        ['signature-experience', 'Experience'], ['signature-projects', 'Portfolio'], ['signature-publications', 'Publications'],
        ['signature-contact', 'Contact'],
    ].filter(([id]) => id === 'signature-home' || id === 'signature-about' ? (id === 'signature-home' || Boolean(summary))
        : id === 'signature-contact' ? Boolean(cv.city || contactHref || cv.linkedin_url)
            : contentSections.some(section => `signature-${section.key}` === id));
    const initials = cv.professional_name.split(/\s+/).slice(0, 2).map(part => part[0]).join('').toUpperCase();

    useEffect(() => {
        const nodes = nav.map(([id]) => document.getElementById(id)).filter(Boolean) as HTMLElement[];
        if (!nodes.length || typeof IntersectionObserver === 'undefined') return;
        const observer = new IntersectionObserver(entries => entries.forEach(entry => entry.isIntersecting && setActive(entry.target.id)), { rootMargin: '-30% 0px -58%' });
        nodes.forEach(node => observer.observe(node));
        return () => observer.disconnect();
    }, [cv.sections]);

    return <article className="signature-portfolio" data-template-key="cv-11">
        <nav className="signature-nav print-hidden" aria-label="Navigasi web portfolio"><a className="signature-brand" href="#signature-home"><i>SK</i><span><b>SAFA KARIR</b><small>Pharmacy Alumni Network</small></span></a><div className="signature-nav-links">{nav.map(([id, label]) => <a className={active === id ? 'active' : ''} href={`#${id}`} key={id}>{label}</a>)}</div>{contactHref && <a className="signature-connect" href={contactHref}>Let&apos;s Connect <span>→</span></a>}</nav>

        <header className="signature-hero" id="signature-home">
            <section className="signature-intro"><p className="signature-eyebrow">PHARMACY ALUMNI PORTFOLIO</p><h1>{cv.professional_name}</h1>{cv.headline && <h2>{cv.headline}</h2>}{summary && <p className="signature-summary">{summary}</p>}<div className="signature-tags">{[...interests, ...skills.map(item => String(item.name ?? ''))].filter(Boolean).slice(0, 4).map(tag => <span key={tag}>{tag}</span>)}</div><div className="signature-actions print-hidden">{contactHref && <a className="primary" href={contactHref}>Get in Touch <span>→</span></a>}{actions?.pdfUrl && <a href={actions.pdfUrl}>↓ Download CV</a>}{actions?.onShare && <button onClick={actions.onShare}>Bagikan</button>}</div></section>
            <figure className="signature-photo">{cv.has_photo ? <img src={photoUrl} alt={`Foto profesional ${cv.professional_name}`} loading="eager" /> : <span>{initials}</span>}</figure>
            <section className="signature-visual" aria-hidden="true"><div className="signature-leaves"><i /><i /><i /></div><div className="signature-books"><span>Pharmaceutical Care</span><span>Clinical Pharmacy</span><span>Better Healthcare</span></div><div className="signature-mortar">✣</div></section>
            <aside className="signature-facts">
                {cv.open_to_work && <div><i>●</i><span><b>Available for Opportunities</b><small>Open to career opportunities</small></span></div>}
                {interests[0] && <div><i>◇</i><span><b>Specialization Interest</b><small>{interests.slice(0, 2).join(' · ')}</small></span></div>}
                {cv.city && <div><i>⌖</i><span><b>Location</b><small>{cv.city}</small></span></div>}
                {contactHref && <div><i>♥</i><span><b>Let&apos;s Collaborate</b><small>Professional connection welcome</small></span></div>}
            </aside>
        </header>

        {quickLinks.length > 0 && <nav className="signature-credentials" aria-label="Tautan kredensial">{quickLinks.map(link => <a href={link.href} key={link.href} target="_blank" rel="noreferrer"><i>{link.icon}</i><span><b>{link.label}</b><small>{link.note}</small></span></a>)}</nav>}

        <main className="signature-content">
            {summary && <section className="signature-about" id="signature-about"><p className="signature-eyebrow">ABOUT</p><h2>Professional profile</h2><p>{summary}</p></section>}
            <div className="signature-grid">{contentSections.map(section => <section className={`signature-section signature-${section.key}`} id={`signature-${section.key}`} key={section.key}><header><span>{icons[section.key] ?? '•'}</span><h2>{titles[section.key] ?? section.title}</h2></header><div>{section.items.map((item, index) => <PortfolioItem section={section.key} item={item} key={index} />)}</div></section>)}</div>
            {(skills.length > 0 || languages.length > 0) && <section className="signature-capabilities"><header><span>◫</span><h2>Skills & Languages</h2></header><div>{skills.map((item, index) => <span key={`skill-${index}`}><b>{String(item.name ?? '')}</b>{item.level && <small>{String(item.level)}</small>}</span>)}{languages.map((item, index) => <span key={`lang-${index}`}><b>{String(item.language ?? '')}</b>{item.proficiency && <small>{String(item.proficiency)}</small>}</span>)}</div></section>}
            {(cv.city || cv.email || cv.whatsapp || cv.linkedin_url) && <section className="signature-contact" id="signature-contact"><header><span>✦</span><h2>Contact</h2></header><div>{cv.city && <span>⌖ {cv.city}</span>}{cv.email && <a href={`mailto:${cv.email}`}>✉ {cv.email}</a>}{cv.whatsapp && <a href={`https://wa.me/${cv.whatsapp.replace(/\D/g, '')}`}>☎ {cv.whatsapp}</a>}{cv.linkedin_url && <a href={cv.linkedin_url} target="_blank" rel="noreferrer">in LinkedIn</a>}</div></section>}
        </main>
        <footer className="signature-footer"><a className="signature-brand" href="#signature-home"><i>SK</i><span><b>SAFA KARIR</b><small>Pharmacy Alumni Network</small></span></a><nav className="print-hidden">{nav.slice(0, 6).map(([id, label]) => <a href={`#${id}`} key={id}>{label}</a>)}</nav>{actions?.onPrint && <button className="print-hidden" onClick={actions.onPrint}>Print Portfolio</button>}</footer>
    </article>;
}
