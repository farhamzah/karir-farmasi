import type { ReactNode } from 'react';

export type CvItem = Record<string, string | number | boolean | null>;
export type TemplateConfiguration = {
    layout: 'single-column' | 'sidebar-main' | 'split';
    photo: 'hidden' | 'circle' | 'oval';
    typography: 'classic' | 'modern' | 'academic';
    spacing: 'comfortable' | 'balanced' | 'compact';
    header_style: 'classic' | 'hero' | 'sidebar';
    section_style: 'rule' | 'accent' | 'plain';
    accent: 'neutral' | 'brand' | 'soft';
    page_padding: 'standard' | 'compact';
};
export type CvPaperData = {
    professional_name: string;
    headline: string | null;
    has_photo: boolean;
    email: string | null;
    whatsapp: string | null;
    city: string | null;
    linkedin_url?: string | null;
    portfolio_url?: string | null;
    template: { key: string; name: string; version: string; configuration: TemplateConfiguration };
    sections: { key: string; title: string; items: CvItem[] }[];
};

const fieldLabels: Record<string, string> = {
    institution_name: 'Institusi', program_name: 'Program studi', degree: 'Gelar', organization: 'Organisasi',
    type: 'Jenis', location: 'Lokasi', category: 'Fokus', level: 'Tingkat', start_date: 'Mulai', end_date: 'Selesai',
    start_year: 'Mulai', end_year: 'Selesai', status: 'Status', issuer: 'Penerbit', issue_date: 'Terbit',
    expiry_date: 'Berlaku hingga', credential_id: 'ID kredensial', role: 'Peran', proficiency: 'Kemahiran',
    publication_name: 'Media', published_on: 'Terbit', doi: 'DOI', currently_active: 'Status', event_type: 'Jenis',
    organizer: 'Penyelenggara', date: 'Tanggal', topics: 'Topik', certificate_number: 'Nomor sertifikat',
    target_roles: 'Bidang / posisi diminati', employment_types: 'Jenis pekerjaan', preferred_locations: 'Lokasi harapan',
    willing_to_relocate: 'Relokasi', availability_date: 'Siap mulai',
};

const primaryFields: Record<string, string[]> = {
    summary: [], education: ['program_name', 'degree'], experience: ['title'], skills: ['name'], certifications: ['title'],
    organizations: ['role'], projects: ['title'], publications: ['title'], languages: ['language'],
    preferences: ['target_roles'], events: ['title'], event_certificates: ['title'],
};

function credentialLabel(item: CvItem): string {
    const title = String(item.title ?? '').toLowerCase();
    if (title.includes('cpob')) return 'Lihat Sertifikat CPOB';
    if (title.includes('halal')) return 'Lihat Sertifikat Halal';
    if (title.includes('kompetensi')) return 'Lihat Sertifikat Kompetensi';
    if (title.includes('iso')) return 'Lihat Sertifikat ISO';
    if (title.includes('ijazah')) return 'Lihat Ijazah';
    if (title.includes('transkrip')) return 'Lihat Transkrip';
    return 'Lihat Sertifikat';
}

function linkLabel(field: string, href: string, item: CvItem): string {
    if (field === 'credential_url') return credentialLabel(item);
    if (field === 'project_url') return 'Lihat Portofolio';
    if (field === 'url') return href.toLowerCase().includes('scholar.google') ? 'Google Scholar' : 'Publikasi';
    return 'Lihat tautan';
}

function LinkChip({ href, label }: { href: string; label: string }) {
    return <a className="cv-link-chip" href={href} target="_blank" rel="noreferrer" aria-label={`${label} — buka tautan`}>{label}<span aria-hidden="true">↗</span></a>;
}

function shortSectionTitle(title: string): string {
    return title.replace('Sertifikat Event', 'Sertifikat').replace('Preferensi Karier', 'Minat Karier');
}

function itemCard(sectionKey: string, item: CvItem, index: number): ReactNode {
    const primary = primaryFields[sectionKey] ?? ['title', 'name'];
    const title = primary.map(key => item[key]).filter(Boolean).join(' · ');
    const description = typeof item.description === 'string' ? item.description : null;
    const links: { href: string; label: string }[] = [];
    const metadata = Object.entries(item).filter(([key, value]) => {
        if (['description', ...primary].includes(key) || value === null || value === '' || value === false) return false;
        if (['credential_url', 'project_url', 'url'].includes(key) && typeof value === 'string') {
            links.push({ href: value, label: linkLabel(key, value, item) });
            return false;
        }
        return typeof value !== 'boolean';
    });

    return <article className="cv-item-card" key={index}>
        {title && <h4>{title}</h4>}
        {metadata.length > 0 && <div className="cv-item-meta">{metadata.map(([key, value]) => <span key={key}>
            {fieldLabels[key] && <small>{fieldLabels[key]}</small>}<b>{String(value)}</b>
        </span>)}</div>}
        {description && <p className="cv-description">{description}</p>}
        {links.length > 0 && <div className="cv-item-links">{links.map(link => <LinkChip key={`${link.label}-${link.href}`} {...link} />)}</div>}
    </article>;
}

export default function CvPaper({ cv, photoUrl = '/profile/photo' }: { cv: CvPaperData; photoUrl?: string }) {
    const config = cv.template.configuration;
    const classes = ['cv-paper', cv.template.key, `layout-${config.layout}`, `photo-${config.photo}`, `type-${config.typography}`,
        `spacing-${config.spacing}`, `header-${config.header_style}`, `section-style-${config.section_style}`,
        `accent-${config.accent}`, `padding-${config.page_padding}`].join(' ');
    const initials = cv.professional_name.trim().split(/\s+/).slice(0, 2).map(part => part[0]).join('').toUpperCase();
    const showPhoto = config.photo !== 'hidden';
    const contacts = [
        cv.city && { key: 'city', label: cv.city, href: null },
        cv.email && { key: 'email', label: cv.email, href: `mailto:${cv.email}` },
        cv.whatsapp && { key: 'whatsapp', label: cv.whatsapp, href: `https://wa.me/${cv.whatsapp.replace(/\D/g, '')}` },
        cv.linkedin_url && { key: 'linkedin', label: 'LinkedIn', href: cv.linkedin_url },
        cv.portfolio_url && { key: 'portfolio', label: 'Portofolio', href: cv.portfolio_url },
    ].filter((contact): contact is { key: string; label: string; href: string | null } => Boolean(contact));
    const sectionsByKey = new Map(cv.sections.map(section => [section.key, section]));
    const onlineLinks = [
        cv.linkedin_url && { href: cv.linkedin_url, label: 'LinkedIn', icon: 'in' },
        cv.portfolio_url && { href: cv.portfolio_url, label: 'Portofolio', icon: '▣' },
        ...((sectionsByKey.get('publications')?.items ?? [])
            .filter(item => typeof item.url === 'string' && String(item.url).toLowerCase().includes('scholar.google'))
            .slice(0, 1)
            .map(item => ({ href: String(item.url), label: 'Google Scholar', icon: '🎓' }))),
        ...((sectionsByKey.get('certifications')?.items ?? [])
            .filter(item => typeof item.credential_url === 'string')
            .slice(0, 4)
            .map(item => ({ href: String(item.credential_url), label: credentialLabel(item), icon: '▤' }))),
    ].filter((link): link is { href: string; label: string; icon: string } => Boolean(link));
    const sidebarSectionKeys = new Set(['skills', 'certifications', 'events', 'event_certificates', 'languages', 'preferences']);
    const renderSection = (section: CvPaperData['sections'][number], sectionIndex: number) => <section id={'portfolio-'+section.key} className={`cv-doc-section section-${section.key}`} key={section.key}>
        <div className="cv-section-heading"><span>{String(sectionIndex + 1).padStart(2, '0')}</span><h3>{shortSectionTitle(section.title)}</h3></div>
        <div className="cv-section-items">{section.items.map((item, index) => itemCard(section.key, item, index))}</div>
    </section>;
    if (cv.template.key === 'cv-06') {
        const sidebarKeys = new Set(['skills', 'languages', 'preferences']);
        const sidebarSections = cv.sections.filter(section => sidebarKeys.has(section.key));
        const mainSections = cv.sections.filter(section => ! sidebarKeys.has(section.key) && section.key !== 'summary');
        const summary = sectionsByKey.get('summary')?.items[0]?.description;

        return <section className={`${classes} cv-web-portfolio`} data-template-key={cv.template.key}>
            <aside className="cv-web-sidebar">
                <div className="cv-web-script">Pharmacy<br />for a Healthier<br />Tomorrow</div>
                <div className="cv-web-photo">{showPhoto && cv.has_photo ? <img src={photoUrl} alt="Foto profil" /> : <span>{initials}</span>}</div>
                <div className="cv-open-card"><b>Open to Work</b><span>Siap berkontribusi di bidang pelayanan dan industri kefarmasian</span></div>
                <section className="cv-side-block"><h3>Kontak</h3>{contacts.filter(contact => ['city', 'email', 'whatsapp'].includes(contact.key)).map(contact => <p key={contact.key}><i>{contact.key === 'city' ? '⌖' : contact.key === 'email' ? '@' : '☎'}</i>{contact.href ? <a href={contact.href}>{contact.label}</a> : contact.label}</p>)}</section>
                {onlineLinks.length > 0 && <section className="cv-side-block"><h3>Profil Online</h3><div className="cv-online-grid">{onlineLinks.map(link => <a href={link.href} key={`${link.label}-${link.href}`} target="_blank" rel="noreferrer"><i>{link.icon}</i>{link.label}</a>)}</div></section>}
                {sidebarSections.map((section, index) => <section className={`cv-side-block side-${section.key}`} key={section.key}><h3>{shortSectionTitle(section.title)}</h3><div className="cv-side-list">{section.items.map((item, itemIndex) => itemCard(section.key, item, itemIndex))}</div></section>)}
                <blockquote>Pelayanan kefarmasian yang berkualitas dimulai dari apoteker yang peduli dan terus belajar.</blockquote>
            </aside>
            <div className="cv-web-main">
                <header className="cv-web-hero">
                    <div><h1>{cv.professional_name}</h1>{cv.headline && <h2>{cv.headline}</h2>}</div>
                    <p>Ilmu kefarmasian untuk masyarakat yang lebih baik</p>
                </header>
                {summary && <section className="cv-web-summary"><span>“</span><p>{String(summary)}</p></section>}
                <div className="cv-web-sections">{mainSections.map((section, index) => renderSection(section, index))}</div>
                <footer className="cv-web-footer">Apoteker untuk kesehatan yang lebih baik</footer>
            </div>
        </section>;
    }
    const documentBody = cv.template.key === 'cv-04'
        ? <div className="cv-document-body">
            <div className="cv-column cv-main-column">{cv.sections.map((section, index) => sidebarSectionKeys.has(section.key) ? null : renderSection(section, index))}</div>
            <div className="cv-column cv-sidebar-column">{cv.sections.map((section, index) => sidebarSectionKeys.has(section.key) ? renderSection(section, index) : null)}</div>
        </div>
        : <div className="cv-document-body">{cv.sections.map((section, sectionIndex) => renderSection(section, sectionIndex))}</div>;

    return <section className={classes} data-template-key={cv.template.key}>
        <header className="cv-document-header">
            {showPhoto && <div className="cv-document-photo">{cv.has_photo ? <img src={photoUrl} alt="Foto profil" /> : <span>{initials}</span>}</div>}
            <div className="cv-identity"><p className="cv-kicker">Curriculum Vitae</p><h1>{cv.professional_name}</h1>{cv.headline && <h2>{cv.headline}</h2>}
                <div className="cv-contact">{contacts.map(contact => <span className={`contact-${contact.key}`} key={contact.key}>
                    <i aria-hidden="true">{contact.key === 'city' ? '⌖' : contact.key === 'email' ? '@' : contact.key === 'whatsapp' ? '◉' : '↗'}</i>
                    {contact.href ? <a href={contact.href} target={contact.href.startsWith('http') ? '_blank' : undefined} rel="noreferrer">{contact.label}</a> : contact.label}
                </span>)}</div>
            </div>
            <aside className="cv-reference-note" aria-hidden="true"><span>Kualitas hari ini untuk masa depan yang lebih sehat</span></aside>
        </header>
        {documentBody}
    </section>;
}
