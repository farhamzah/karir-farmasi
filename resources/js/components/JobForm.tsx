import { Link, useForm } from '@inertiajs/react';

export type Existing = Partial<{
    public_reference: string; employer_display_name: string; title: string; employment_type: string; work_mode: string;
    city: string; location_text: string; description: string; requirements: string; responsibilities: string;
    education_requirement: string; experience_requirement: string; salary_min: number; salary_max: number;
    salary_visible: boolean; openings: number; application_method: string; external_apply_url: string;
    external_apply_email: string; application_instruction: string; expires_at: string; tags_text: string;
    source_type: string; source_name: string; source_reference: string; received_at: string;
    source_verified_at: string; internal_notes: string; has_flyer: boolean; flyer_url: string; flyer_alt_text: string;
}>;

type JobFormData = {
    employer_display_name: string; title: string; employment_type: string; work_mode: string; city: string;
    location_text: string; description: string; requirements: string; responsibilities: string;
    education_requirement: string; experience_requirement: string; salary_min: number | string;
    salary_max: number | string; salary_visible: boolean; openings: number | string; application_method: string;
    external_apply_url: string; external_apply_email: string; application_instruction: string; expires_at: string;
    tags: string; source_type: string; source_name: string; source_reference: string; received_at: string;
    source_verified: boolean; internal_notes: string; flyer: File | null; flyer_alt_text: string;
    source_attachment: File | null;
};

export default function JobForm({ job, audience }: { job: Existing | null; audience: 'company' | 'admin' }) {
    const form = useForm<JobFormData>({
        employer_display_name: job?.employer_display_name || '', title: job?.title || '', employment_type: job?.employment_type || 'full_time',
        work_mode: job?.work_mode || 'onsite', city: job?.city || '', location_text: job?.location_text || '', description: job?.description || '',
        requirements: job?.requirements || '', responsibilities: job?.responsibilities || '', education_requirement: job?.education_requirement || '',
        experience_requirement: job?.experience_requirement || '', salary_min: job?.salary_min || '', salary_max: job?.salary_max || '',
        salary_visible: Boolean(job?.salary_visible), openings: job?.openings || '', application_method: job?.application_method || 'internal',
        external_apply_url: job?.external_apply_url || '', external_apply_email: job?.external_apply_email || '',
        application_instruction: job?.application_instruction || '', expires_at: job?.expires_at?.slice(0, 10) || '', tags: job?.tags_text || '',
        source_type: job?.source_type || 'campus_input', source_name: job?.source_name || '', source_reference: job?.source_reference || '',
        received_at: job?.received_at?.slice(0, 10) || '', source_verified: Boolean(job?.source_verified_at), internal_notes: job?.internal_notes || '',
        flyer: null, flyer_alt_text: job?.flyer_alt_text || '', source_attachment: null,
    });
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const base = audience === 'company' ? '/company/jobs' : '/admin/jobs';
        const options = { forceFormData: true };
        if (job?.public_reference) {
            form.transform(data => ({ ...data, _method: 'put' }));
            form.post(`${base}/${job.public_reference}`, options);
            return;
        }
        form.post(base, options);
    };
    const set = (key: keyof JobFormData) => (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => form.setData(key, event.target.value);

    return <form className="job-form form-stack" onSubmit={submit}>
        {audience === 'admin' && <div className="job-entry-methods" aria-label="Pilihan cara memasukkan lowongan">
            <article className="active"><span>01</span><div><strong>Tulis lowongan</strong><small>Untuk informasi berbentuk kalimat.</small></div></article>
            <article className="active"><span>02</span><div><strong>Unggah flyer</strong><small>Untuk poster JPG, PNG, atau WebP.</small></div></article>
            <Link href="/admin/jobs/import"><span>03</span><div><strong>Import CSV</strong><small>Hanya untuk banyak lowongan sekaligus.</small></div></Link>
        </div>}

        {audience === 'admin' && <section className="job-flyer-input">
            <div><p className="eyebrow">FLYER PUBLIK · OPSIONAL</p><h2>Punya poster lowongan?</h2><p>Unggah gambarnya di sini. Flyer tampil di daftar dan detail setelah draft dipublikasikan.</p></div>
            <label className="file-drop">Pilih gambar flyer
                <input type="file" accept="image/jpeg,image/png,image/webp" onChange={event => form.setData('flyer', event.target.files?.[0] || null)} />
                <small>{form.data.flyer?.name || (job?.has_flyer ? 'Flyer saat ini tetap digunakan' : 'JPG, PNG, atau WebP · maks. 5 MB')}</small>
            </label>
            {job?.flyer_url && <a className="job-existing-flyer" href={job.flyer_url} target="_blank" rel="noreferrer"><img src={job.flyer_url} alt={job.flyer_alt_text || 'Flyer lowongan saat ini'} /><span>Lihat flyer saat ini ↗</span></a>}
            <label>Teks alternatif gambar<input value={form.data.flyer_alt_text} onChange={set('flyer_alt_text')} placeholder="Contoh: Flyer lowongan QA PT Farmasi Sehat" /></label>
            {form.errors.flyer && <p className="field-error">{form.errors.flyer}</p>}
        </section>}

        <div className="form-grid">
            {audience === 'admin' && <label>Nama pemberi kerja<input value={form.data.employer_display_name} onChange={set('employer_display_name')} required /></label>}
            <label>Judul lowongan<input value={form.data.title} onChange={set('title')} required /></label>
            <label>Jenis pekerjaan<select value={form.data.employment_type} onChange={set('employment_type')}><option value="full_time">Penuh waktu</option><option value="part_time">Paruh waktu</option><option value="contract">Kontrak</option><option value="internship">Magang</option><option value="project">Proyek</option><option value="temporary">Sementara</option></select></label>
            <label>Mode kerja<select value={form.data.work_mode} onChange={set('work_mode')}><option value="onsite">Onsite</option><option value="hybrid">Hybrid</option><option value="remote">Remote</option></select></label>
            <label>Kota<input value={form.data.city} onChange={set('city')} /></label>
            <label>Lokasi rinci<input value={form.data.location_text} onChange={set('location_text')} /></label>
            <label className="wide">Keterangan lowongan <small>{audience === 'admin' ? 'Boleh kosong jika flyer sudah diunggah.' : 'Wajib diisi.'}</small><textarea rows={6} value={form.data.description} onChange={set('description')} required={audience === 'company'} placeholder="Tuliskan gambaran posisi dan informasi penting lainnya." /></label>
            <label className="wide">Tanggung jawab<textarea rows={4} value={form.data.responsibilities} onChange={set('responsibilities')} /></label>
            <label className="wide">Kualifikasi<textarea rows={4} value={form.data.requirements} onChange={set('requirements')} /></label>
            <label>Pendidikan<input value={form.data.education_requirement} onChange={set('education_requirement')} /></label>
            <label>Pengalaman<input value={form.data.experience_requirement} onChange={set('experience_requirement')} /></label>
            <label>Tag terstruktur<input value={form.data.tags} onChange={set('tags')} placeholder="CPOB, QA/QC, Regulatory, Apotek" /></label>
            <label>Batas lamaran<input type="date" value={form.data.expires_at} onChange={set('expires_at')} /></label>
            <label>Metode lamaran<select value={form.data.application_method} onChange={set('application_method')}><option value="internal">Internal SAFA</option><option value="external_url">Tautan eksternal</option><option value="email_instruction">Email</option></select></label>
            {form.data.application_method === 'external_url' && <label>URL resmi<input type="url" value={form.data.external_apply_url} onChange={set('external_apply_url')} required /></label>}
            {form.data.application_method === 'email_instruction' && <label>Email lamaran<input type="email" value={form.data.external_apply_email} onChange={set('external_apply_email')} required /></label>}
            <label className="wide">Petunjuk melamar<textarea rows={3} value={form.data.application_instruction} onChange={set('application_instruction')} placeholder="Contoh: Cantumkan posisi pada subjek email." /></label>
        </div>

        {audience === 'admin' && <fieldset><legend>Sumber dan verifikasi</legend><p className="fieldset-help">Bagian ini membantu kampus memeriksa kebenaran lowongan. Bukti sumber tidak ditampilkan kepada alumni.</p><div className="form-grid">
            <label>Jenis sumber<select value={form.data.source_type} onChange={set('source_type')}><option value="campus_input">Input kampus</option><option value="partner">Mitra</option><option value="public_source">Sumber publik</option><option value="company_direct">Langsung perusahaan</option></select></label>
            <label>Nama sumber<input value={form.data.source_name} onChange={set('source_name')} required /></label>
            <label>URL sumber<input type="url" value={form.data.source_reference} onChange={set('source_reference')} /></label>
            <label>Diterima tanggal<input type="date" value={form.data.received_at} onChange={set('received_at')} /></label>
            <label className="wide">Bukti sumber privat<input type="file" accept="application/pdf,image/jpeg,image/png" onChange={event => form.setData('source_attachment', event.target.files?.[0] || null)} /><small>PDF/JPG/PNG, hanya untuk petugas.</small></label>
            <label className="check-label"><input type="checkbox" checked={form.data.source_verified} onChange={event => form.setData('source_verified', event.target.checked)} /> Sumber sudah diverifikasi</label>
            <label className="wide">Catatan internal<textarea value={form.data.internal_notes} onChange={set('internal_notes')} /></label>
        </div></fieldset>}
        {Object.keys(form.errors).length > 0 && <div className="alert error"><strong>Lowongan belum dapat disimpan.</strong>{Object.values(form.errors).map((error, index) => <span key={index}>{error}</span>)}</div>}
        <button className="primary-button" disabled={form.processing}>{form.processing ? 'Menyimpan…' : 'Simpan sebagai draft'}</button>
    </form>;
}
