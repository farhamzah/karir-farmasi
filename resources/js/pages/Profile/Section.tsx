import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import AppHeader from '../../components/AppHeader';
import { candidateNav } from '../../components/CandidateNav';

type Field = { name: string; label: string; type: 'text' | 'number' | 'date' | 'url' | 'textarea' | 'select' | 'checkbox' | 'file'; required?: boolean; options?: string[]; min?: number; max?: number; step?: string };
type SectionDefinition = { key: string; label: string; singular: boolean; empty: string; fields: Field[] };
type FormValue = string | boolean | File | null;
type RecordData = { id: number; attachment_available?: boolean; source?: string; verified?: boolean; [key: string]: unknown };

function blankData(section: SectionDefinition): Record<string, FormValue> {
    const entries = section.fields.map((field) => [field.name, field.type === 'checkbox' ? false : '']);
    if (!section.singular) entries.push(['is_visible', true]);
    return Object.fromEntries(entries);
}

export default function Section({ section, records }: { section: SectionDefinition; records: RecordData[] }) {
    const page = usePage<{ flash?: { success?: string } }>();
    const [editingId, setEditingId] = useState<number | null>(null);
    const form = useForm<Record<string, FormValue>>(blankData(section));

    const reset = () => { setEditingId(null); form.setData(blankData(section)); form.clearErrors(); };
    const edit = (record: RecordData) => {
        const data = blankData(section);
        section.fields.forEach((field) => {
            const value = record[field.name];
            data[field.name] = Array.isArray(value) ? value.join(', ') : typeof value === 'boolean' ? value : value == null ? '' : String(value);
        });
        if (!section.singular) data.is_visible = record.is_visible !== false;
        setEditingId(record.id);
        form.setData(data);
    };
    const submit = (event: FormEvent) => {
        event.preventDefault();
        const url = editingId === null ? `/profile/sections/${section.key}` : `/profile/sections/${section.key}/${editingId}`;
        form.post(url, { forceFormData: section.fields.some((field) => field.type === 'file'), onSuccess: reset });
    };

    return <><Head title={section.label} /><main className="profile-page app-surface"><AppHeader context={section.label.toUpperCase()} nav={candidateNav('profile')} actions={<span className="status-dot">Milik Anda</span>} logout />
        <section className="profile-shell section-editor"><div><p className="eyebrow">BAGIAN PROFIL</p><h1 className="page-title">{section.label}.</h1><p className="page-lead">Simpan data profesional Anda tanpa mengubah profil Core.</p></div>
            {page.props.flash?.success && <div className="alert success">{page.props.flash.success}</div>}
            <div className="section-layout"><div className="record-list">{records.length === 0 && <div className="empty-profile-state"><strong>{section.empty}</strong><span>Tambahkan data saat sudah siap. Kolom kosong tidak dianggap sebagai status pekerjaan.</span></div>}
                {records.map((record) => <article className="profile-record" key={record.id}><div className="record-heading"><strong>{String(record[section.fields[0]?.name] || section.label)}</strong>{record.verified && <span className="verified-badge">Core verified</span>}{record.source === 'user_declared' && <span className="declared-badge">Deklarasi pengguna</span>}</div>
                    <dl>{section.fields.slice(1).filter((field) => field.type !== 'file' && record[field.name] !== null && record[field.name] !== '').map((field) => <div key={field.name}><dt>{field.label}</dt><dd>{Array.isArray(record[field.name]) ? (record[field.name] as unknown[]).join(', ') : String(record[field.name])}</dd></div>)}</dl>
                    {record.attachment_available && <Link className="text-link" href={`/profile/files/${section.key}/${record.id}`}>Unduh lampiran privat</Link>}
                    <div className="record-actions"><button className="text-button" onClick={() => edit(record)}>Edit</button><button className="text-button danger-text" onClick={() => router.delete(`/profile/sections/${section.key}/${record.id}`)}>Hapus</button></div>
                </article>)}</div>
                <form className="profile-form-card section-form" onSubmit={submit}><h2>{editingId === null ? (section.singular && records.length ? 'Perbarui data' : 'Tambah data') : 'Edit data'}</h2><div className="form-stack">{section.fields.map((field) => <label className={field.type === 'checkbox' ? 'check-row' : ''} key={field.name}>{field.type === 'checkbox' ? <><input type="checkbox" checked={Boolean(form.data[field.name])} onChange={(e) => form.setData(field.name, e.target.checked)} /><span>{field.label}</span></> : <><span>{field.label}</span>{field.type === 'textarea' ? <textarea value={String(form.data[field.name] ?? '')} onChange={(e) => form.setData(field.name, e.target.value)} required={field.required} /> : field.type === 'select' ? <select value={String(form.data[field.name] ?? '')} onChange={(e) => form.setData(field.name, e.target.value)} required={field.required}><option value="">Pilih</option>{field.options?.map((option) => <option value={option} key={option}>{option}</option>)}</select> : field.type === 'file' ? <input type="file" accept=".pdf,.jpg,.jpeg,.png" onChange={(e) => form.setData(field.name, e.target.files?.[0] || null)} /> : <input type={field.type} value={String(form.data[field.name] ?? '')} onChange={(e) => form.setData(field.name, e.target.value)} required={field.required} min={field.min} max={field.max} step={field.step} />}</>}</label>)}
                    {!section.singular && <label className="check-row"><input type="checkbox" checked={Boolean(form.data.is_visible)} onChange={(e) => form.setData('is_visible', e.target.checked)} /><span>Tampilkan pada CV/publik nanti</span></label>}
                    {Object.entries(form.errors).map(([key, message]) => <p className="field-error" key={key}>{message}</p>)}</div><div className="form-actions">{editingId !== null && <button type="button" className="secondary-button" onClick={reset}>Batal edit</button>}<button className="primary-button" disabled={form.processing}>Simpan</button></div></form>
            </div>
        </section>
    </main></>;
}
