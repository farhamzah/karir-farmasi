import { Head } from '@inertiajs/react';
import CvPaper, { type CvPaperData } from './CvPaper';

type Share = { url: string; allow_pdf_download: boolean; pdf_url: string | null };

export default function PublicCv({ cv, share, photoUrl }: { cv: CvPaperData; share: Share; photoUrl: string | null }) {
    const copy = async () => navigator.clipboard.writeText(share.url);
    const nativeShare = async () => {
        if (navigator.share) await navigator.share({ title: `CV ${cv.professional_name}`, url: share.url });
        else await copy();
    };
    const encoded = encodeURIComponent(share.url);
    const title = encodeURIComponent(`CV ${cv.professional_name}`);

    const navigation = cv.sections.filter(section => ['education','experience','skills','events','event_certificates','projects','certifications'].includes(section.key));
    const portfolioSections = cv.sections.filter(section => ['events','event_certificates'].includes(section.key));
    return <><Head title={`CV ${cv.professional_name}`}><meta name="robots" content="noindex,nofollow" /><meta name="referrer" content="no-referrer" /></Head><main className="public-cv-page"><header className="public-utility print-hidden"><div><span className="public-brand-mark">SK</span><span><strong>SAFA KARIR</strong><small>Portofolio Alumni Farmasi UBP</small></span></div><nav>{share.allow_pdf_download && share.pdf_url && <a className="utility-primary" href={share.pdf_url}>Unduh PDF</a>}<button onClick={() => window.print()}>Cetak</button><button onClick={copy}>Salin tautan</button><button onClick={nativeShare}>Bagikan</button></nav></header>
        {navigation.length>0&&<nav className="portfolio-nav print-hidden" aria-label="Navigasi portofolio">{navigation.map(section=><a key={section.key} href={'#portfolio-'+section.key}>{section.title}</a>)}</nav>}
        <div className="public-intro print-hidden"><div><p className="eyebrow">PROFIL PROFESIONAL</p><h1>Karya, kompetensi, dan<br />langkah profesional berikutnya.</h1><p>CV terpilih dari ruang karier alumni Farmasi UBP.</p></div><div className="social-share"><span>Bagikan melalui</span><a href={`https://wa.me/?text=${title}%20${encoded}`} rel="noreferrer">WhatsApp</a><a href={`https://www.linkedin.com/sharing/share-offsite/?url=${encoded}`} rel="noreferrer">LinkedIn</a><a href={`mailto:?subject=${title}&body=${encoded}`}>Email</a></div></div>
        <CvPaper cv={cv} photoUrl={photoUrl ?? undefined} />
        {portfolioSections.length>0&&<section className="interactive-portfolio print-hidden"><p className="eyebrow">JEJAK PEMBELAJARAN TERVERIFIKASI</p><h2>Event & sertifikat pilihan.</h2><div>{portfolioSections.flatMap(section=>section.items.map((item,index)=><details key={section.key+'-'+index}><summary><span>{section.key==='events'?'✦':'✓'}</span><b>{String(item.title||'Kegiatan Farmasi')}</b><small>{section.title}</small></summary><p>{String(item.description||item.organizer||item.issuer||'Terverifikasi melalui SAFA KARIR.')}</p>{item.topics&&<div className="topic-row"><span>{String(item.topics)}</span></div>}</details>))}</div></section>}
        <footer className="public-footer print-hidden"><strong>SAFA KARIR</strong><span>Farmasi UBP · Tumbuh dengan kompetensi, melangkah dengan percaya diri.</span></footer></main></>;
}
