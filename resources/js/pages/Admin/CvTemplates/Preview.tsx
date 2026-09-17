import { Head, Link } from '@inertiajs/react';
import CvPaper, { type CvPaperData, type TemplateConfiguration } from '../../Cv/CvPaper';

type Fixture = Omit<CvPaperData, 'template'>;
type Template = { id: number; key: string; name: string; version: string; configuration: TemplateConfiguration };

export default function TemplatePreview({ template, fixture }: { template: Template; fixture: Fixture }) {
    const cv: CvPaperData = { ...fixture, template };
    return <><Head title={`Preview ${template.name}`} /><main className="cv-preview-page admin-template-preview">
        <header className="compact-header"><Link className="brand" href={`/admin/cv-templates/${template.id}`}><span className="brand-mark">SK</span><span><strong>SAFA KARIR</strong><small>PREVIEW TEMPLATE</small></span></Link><Link className="secondary-button" href={`/admin/cv-templates/${template.id}`}>Kembali ke editor</Link></header>
        <div className="preview-toolbar"><div><strong>{template.name}</strong><span>{template.key} · {template.version} · fixture sintetis</span></div><span className="safe-badge">Konfigurasi allowlist</span></div>
        <CvPaper cv={cv} photoUrl="" /><p className="preview-note">Data pada preview admin seluruhnya sintetis dan tidak berasal dari profil alumni.</p>
    </main></>;
}
