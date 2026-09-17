import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Brand from '../components/Brand';

export default function Register() {
    const page = usePage<{ errors?: Record<string, string> }>();
    const form = useForm({ student_number: '', full_name: '', claimed_program: '', graduation_year: '', personal_email: '', whatsapp: '', password: '', password_confirmation: '' });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return <><Head title="Daftar Alumni" /><main className="form-page app-surface">
        <header className="compact-header app-header"><Brand /><div className="app-header-actions"><span className="header-prompt">Sudah terdaftar?</span><Link className="ghost-button" href="/login">Masuk</Link></div></header>
        <section className="form-card register-card"><div className="register-intro"><p className="eyebrow">PENDAFTARAN ALUMNI</p><h1 className="page-title">Verifikasi alumni.<br /><em>Mulai perjalanan karier Anda.</em></h1><p className="page-lead">Daftarkan data alumni Anda untuk diverifikasi admin Farmasi UBP. Setelah disetujui, Anda dapat masuk, membangun profil profesional, dan menggunakan layanan karier tanpa memperbarui profil Core.</p><div className="step-strip"><span className="active"><b>1</b> Data minimum</span><span><b>2</b> Verifikasi admin</span><span><b>3</b> Masuk Karir</span></div></div>
            {page.props.errors?.registration && <div className="alert error">{page.props.errors.registration}</div>}
            <form className="form-grid" onSubmit={submit}>
                <label>NIM<input inputMode="numeric" placeholder="Contoh: 21416274201001" value={form.data.student_number} onChange={(event) => form.setData('student_number', event.target.value)} required /></label>
                <label>Nama lengkap<input autoComplete="name" placeholder="Contoh: Anisa Susanti" value={form.data.full_name} onChange={(event) => form.setData('full_name', event.target.value)} required /></label>
                <label>Prodi/jenjang yang diklaim<input placeholder="Contoh: S1 Farmasi" value={form.data.claimed_program} onChange={(event) => form.setData('claimed_program', event.target.value)} required /></label>
                <label>Tahun lulus <small>opsional</small><input type="number" min="1950" max="2100" placeholder="Contoh: 2026" value={form.data.graduation_year} onChange={(event) => form.setData('graduation_year', event.target.value)} /></label>
                <label>Email pribadi<input type="email" autoComplete="email" placeholder="Contoh: nama@email.com" value={form.data.personal_email} onChange={(event) => form.setData('personal_email', event.target.value)} required /></label>
                <label>WhatsApp<input type="tel" autoComplete="tel" placeholder="Contoh: 0812 3456 7890" value={form.data.whatsapp} onChange={(event) => form.setData('whatsapp', event.target.value)} required /></label>
                <label>Password<input type="password" autoComplete="new-password" minLength={8} placeholder="Minimal 8 karakter" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} required /></label>
                <label>Ulangi password<input type="password" autoComplete="new-password" minLength={8} placeholder="Ketik ulang password yang sama" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} required /></label>
                <div className="form-wide info-box"><strong>Setelah dikirim</strong><span>Admin Farmasi UBP akan memeriksa data Anda. Jika akun Core lama ditemukan, password Core lama tetap digunakan dan tidak akan ditimpa.</span></div>
                {Object.entries(form.errors).filter(([key]) => key !== 'registration').map(([key, message]) => <p className="field-error form-wide" key={key}>{message}</p>)}
                <button className="primary-button form-wide" disabled={form.processing}>Kirim pendaftaran</button>
            </form>
        </section>
    </main></>;
}
