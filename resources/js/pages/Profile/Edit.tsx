import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AppHeader from '../../components/AppHeader';
import { candidateNav } from '../../components/CandidateNav';

type ProfileForm = {
    professional_name: string;
    headline: string;
    professional_summary: string;
    professional_email: string;
    whatsapp: string;
    city: string;
    linkedin_url: string;
    portfolio_url: string;
    open_to_work: boolean;
    profile_visibility: 'private' | 'searchable';
    section_visibility: Record<string, boolean>;
};

type Profile = Omit<ProfileForm, 'professional_name' | 'headline' | 'professional_summary' | 'professional_email' | 'whatsapp' | 'city' | 'linkedin_url' | 'portfolio_url'> & {
    professional_name: string | null;
    headline: string | null;
    professional_summary: string | null;
    professional_email: string | null;
    whatsapp: string | null;
    city: string | null;
    linkedin_url: string | null;
    portfolio_url: string | null;
    has_photo: boolean;
    discoverable_by_verified_companies: boolean;
    discoverable_by_internal_leadership: boolean;
    discoverability_updated_at: string | null;
};

const visibilitySections = ['education', 'experience', 'skills', 'certifications', 'organizations', 'projects', 'publications', 'languages'];

export default function Edit({ profile }: { profile: Profile }) {
    const form = useForm<ProfileForm>({
        professional_name: profile.professional_name || '',
        headline: profile.headline || '',
        professional_summary: profile.professional_summary || '',
        professional_email: profile.professional_email || '',
        whatsapp: profile.whatsapp || '',
        city: profile.city || '',
        linkedin_url: profile.linkedin_url || '',
        portfolio_url: profile.portfolio_url || '',
        open_to_work: profile.open_to_work,
        profile_visibility: profile.profile_visibility,
        section_visibility: profile.section_visibility || {},
    });
    const photo = useForm<{ photo: File | null }>({ photo: null });
    const discoverability = useForm({
        discoverable_by_verified_companies: profile.discoverable_by_verified_companies,
        discoverable_by_internal_leadership: profile.discoverable_by_internal_leadership,
    });
    const submit = (event: FormEvent) => { event.preventDefault(); form.put('/profile'); };
    const uploadPhoto = (event: FormEvent) => { event.preventDefault(); photo.post('/profile/photo', { forceFormData: true }); };

    return <><Head title="Edit Profil" /><main className="profile-page app-surface"><AppHeader context="EDIT PROFIL" nav={candidateNav('profile')} actions={<span className="status-dot">Data privat Karir</span>} logout />
        <section className="profile-shell edit-shell"><div><p className="eyebrow">EDIT PROFIL</p><h1 className="page-title">Identitas profesional.</h1><p className="page-lead">Isi seperlunya dan simpan bertahap. Akun dan profil Core tidak diubah.</p></div>
            <section className="profile-form-card discoverability-card"><div><p className="eyebrow">DIREKTORI TALENTA</p><h2>Anda menentukan siapa yang dapat menemukan profil.</h2><p>Izin ini terpisah dari public link CV dan dapat dicabut kapan saja. Kontak, tracer, ID Core, serta file privat tidak masuk hasil pencarian.</p></div><form onSubmit={(event) => { event.preventDefault(); discoverability.put('/profile/discoverability'); }}><label className="consent-option"><input type="checkbox" checked={discoverability.data.discoverable_by_verified_companies} onChange={(e) => discoverability.setData('discoverable_by_verified_companies', e.target.checked)} /><span><strong>Perusahaan terverifikasi</strong><small>Profil profesional dapat dicari oleh rekruter dari perusahaan yang aktif dan disetujui admin.</small></span></label><label className="consent-option"><input type="checkbox" checked={discoverability.data.discoverable_by_internal_leadership} onChange={(e) => discoverability.setData('discoverable_by_internal_leadership', e.target.checked)} /><span><strong>Kaprodi / Dekan ber-assignment</strong><small>Direktori internal hanya menampilkan Anda di program yang menjadi lingkup pimpinan.</small></span></label><button className="primary-button" disabled={discoverability.processing}>Simpan izin direktori</button>{profile.discoverability_updated_at&&<small>Terakhir diubah {new Date(profile.discoverability_updated_at).toLocaleDateString('id-ID')}</small>}</form></section>
            <section className="profile-form-card"><h2>Foto profil</h2><p>JPG, PNG, atau WebP maksimal 2 MB. File disimpan privat dan hanya ditampilkan melalui pemeriksaan ownership.</p><form className="inline-upload" onSubmit={uploadPhoto}><input type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => photo.setData('photo', event.target.files?.[0] || null)} required /><button className="secondary-button" disabled={photo.processing}>Unggah foto</button></form>{photo.errors.photo && <p className="field-error">{photo.errors.photo}</p>}{profile.has_photo && <button className="text-button danger-text" onClick={() => router.delete('/profile/photo')}>Hapus foto</button>}</section>
            <form className="profile-edit-grid" onSubmit={submit}>
                <section className="profile-form-card"><h2>Ringkasan profesional</h2><div className="form-stack"><label>Nama profesional<input value={form.data.professional_name} onChange={(e) => form.setData('professional_name', e.target.value)} /></label><label>Headline<input value={form.data.headline} onChange={(e) => form.setData('headline', e.target.value)} placeholder="Contoh: Apoteker · Pelayanan Klinis" /></label><label>Ringkasan<textarea value={form.data.professional_summary} onChange={(e) => form.setData('professional_summary', e.target.value)} /></label><label>Kota/domisili umum<input value={form.data.city} onChange={(e) => form.setData('city', e.target.value)} /></label></div></section>
                <section className="profile-form-card"><h2>Kontak profesional</h2><p>Kontak ini terpisah dari email login Core.</p><div className="form-stack"><label>Email profesional<input type="email" value={form.data.professional_email} onChange={(e) => form.setData('professional_email', e.target.value)} /></label><label>WhatsApp<input value={form.data.whatsapp} onChange={(e) => form.setData('whatsapp', e.target.value)} /></label><label>LinkedIn<input type="url" value={form.data.linkedin_url} onChange={(e) => form.setData('linkedin_url', e.target.value)} /></label><label>Portofolio<input type="url" value={form.data.portfolio_url} onChange={(e) => form.setData('portfolio_url', e.target.value)} /></label></div></section>
                <section className="profile-form-card"><h2>Privasi &amp; kesiapan</h2><div className="form-stack"><label className="check-row"><input type="checkbox" checked={form.data.open_to_work} onChange={(e) => form.setData('open_to_work', e.target.checked)} /><span>Terbuka untuk peluang</span></label><label>Visibilitas dasar profil<select value={form.data.profile_visibility} onChange={(e) => form.setData('profile_visibility', e.target.value as ProfileForm['profile_visibility'])}><option value="private">Privat</option><option value="searchable">Siap untuk pengaturan direktori</option></select></label><p className="form-note">Izin perusahaan dan pimpinan diatur terpisah pada panel Direktori Talenta di atas.</p></div></section>
                <section className="profile-form-card"><h2>Bagian untuk CV/publik mendatang</h2><p>Menyembunyikan bagian tidak menghapus data sumber.</p><div className="visibility-list">{visibilitySections.map((section) => <label className="check-row" key={section}><input type="checkbox" checked={form.data.section_visibility[section] ?? true} onChange={(e) => form.setData('section_visibility', { ...form.data.section_visibility, [section]: e.target.checked })} /><span>{section.replace('_', ' ')}</span></label>)}</div></section>
                {Object.entries(form.errors).map(([key, message]) => <p className="field-error profile-form-error" key={key}>{message}</p>)}
                <div className="form-actions"><Link className="secondary-button" href="/profile">Batal</Link><button className="primary-button" disabled={form.processing}>Simpan profil</button></div>
            </form>
        </section>
    </main></>;
}

