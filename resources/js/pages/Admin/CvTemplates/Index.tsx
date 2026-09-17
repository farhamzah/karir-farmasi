import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AppHeader from '../../../components/AppHeader';

type Template = { id: number; key: string; name: string; description: string; active: boolean; current_version: string | null; usage_count: number; has_draft: boolean; display_order: number; updated_at: string };
type BaseTemplate = { key: string; name: string };

export default function TemplateIndex({ templates, baseTemplates }: { templates: Template[]; baseTemplates: BaseTemplate[] }) {
    const flash = usePage<{ flash?: { success?: string } }>().props.flash;
    const form = useForm({ key: '', name: '', description: '', base_template_key: baseTemplates[0]?.key ?? '' });
    const create = () => form.post('/admin/cv-templates');
    const move = (index: number, delta: number) => {
        const next = [...templates]; const target = index + delta; if (target < 0 || target >= next.length) return;
        [next[index], next[target]] = [next[target], next[index]];
        router.put('/admin/cv-templates/order', { templates: next.map((template, order) => ({ id: template.id, display_order: (order + 1) * 10 })) });
    };

    return <><Head title="Template CV" /><main className="admin-page app-surface"><AppHeader context="STUDIO TEMPLATE" home="/staff" nav={[{ label: 'Overview', href: '/staff' }, { label: 'Registrasi', href: '/admin/registrations' }, { label: 'Template CV', href: '/admin/cv-templates', active: true }]} logout />
        <section className="admin-content template-admin-shell"><div className="cv-title-row"><div><p className="eyebrow">ADMIN TEMPLATE CV</p><h1 className="page-title">Kurasi tampilan<br /><em>yang siap berkarya.</em></h1><p className="page-lead">Kelola identitas visual melalui token aman. Setiap publikasi menjadi versi tetap agar CV alumni tidak berubah diam-diam.</p></div><span className="safe-badge">Tanpa kode mentah</span></div>
        {flash?.success && <div className="alert success">{flash.success}</div>}
        <div className="template-admin-grid">{templates.map((template, index) => <article className="template-admin-card" key={template.id}><div className={`template-miniature ${template.key}`}><i /><b /><em /></div><div><span className={`status-pill ${template.active ? 'active' : 'retired'}`}>{template.active ? 'Aktif' : 'Pensiun'}</span><code>{template.key}</code><h2>{template.name}</h2><p>{template.description}</p><small>v{template.current_version ?? '—'} · {template.usage_count} CV · {template.has_draft ? 'ada draf' : 'tanpa draf'}</small></div><div className="template-card-actions"><Link href={`/admin/cv-templates/${template.id}`}>Kelola</Link><Link href={`/admin/cv-templates/${template.id}/preview`}>Preview</Link><button onClick={() => move(index, -1)} disabled={index === 0}>↑</button><button onClick={() => move(index, 1)} disabled={index === templates.length - 1}>↓</button></div></article>)}</div>
        <form className="profile-form-card template-create-card" onSubmit={event => { event.preventDefault(); create(); }}><div><p className="eyebrow">VARIASI KEENAM</p><h2>Buat dari layout yang didukung.</h2><p>Salin konfigurasi aman sebagai draf, lalu sesuaikan token sebelum diterbitkan.</p></div><div className="form-grid"><label>Key<input value={form.data.key} onChange={event => form.setData('key', event.target.value)} placeholder="contoh: cv-komunitas" required /></label><label>Template dasar<select value={form.data.base_template_key} onChange={event => form.setData('base_template_key', event.target.value)}>{baseTemplates.map(base => <option key={base.key} value={base.key}>{base.name}</option>)}</select></label><label>Nama<input value={form.data.name} onChange={event => form.setData('name', event.target.value)} required /></label><label className="form-wide">Deskripsi<textarea value={form.data.description} onChange={event => form.setData('description', event.target.value)} required /></label></div>{Object.keys(form.errors).length > 0 && <div className="alert error">Periksa key, nama, deskripsi, dan template dasar.</div>}<button className="primary-button" disabled={form.processing}>Buat draf variasi</button></form>
        </section></main></>;
}
