import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Brand from '../components/Brand';

type Props = { coreRecoveryUrl: string | null };

export default function Login({ coreRecoveryUrl }: Props) {
    const form = useForm({ identifier: '', password: '' });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/internal/session', { onFinish: () => form.reset('password') });
    };

    return <><Head title="Login" /><main className="portal-shell auth-redesign">
        <section className="auth-panel">
            <div className="auth-brand-row"><Brand /><Link className="back-link" href="/">← Beranda</Link></div>
            <div className="auth-copy"><p className="eyebrow">SELAMAT DATANG KEMBALI</p><h1 className="page-title">Lanjutkan perjalanan<br /><em>karier Anda.</em></h1>
            <p className="page-lead">Masuk dengan email, username, atau NIM yang telah terdaftar. Gunakan password akun SAFA/Core Anda.</p></div>
            <form className="form-stack" onSubmit={submit}>
                <label>Identitas <small>Email, username, atau NIM</small><input value={form.data.identifier} onChange={(event) => form.setData('identifier', event.target.value)} autoComplete="username" placeholder="Masukkan identitas Anda" required /></label>
                {form.errors.identifier && <p className="field-error">{form.errors.identifier}</p>}
                <label>Password<input type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="current-password" placeholder="Masukkan password" required /></label>
                <button className="primary-button" disabled={form.processing}>Masuk ke Karir</button>
            </form>
            <div className="auth-links"><Link href="/register">Belum terdaftar? Daftar alumni</Link><Link href="/status">Cek status pendaftaran</Link>{coreRecoveryUrl && <a href={coreRecoveryUrl}>Lupa password Core</a>}</div>
        </section>
        <aside className="auth-aside"><div className="auth-aside-art"><span className="auth-orbit">SK</span><i /><i /><i /></div><div><span>RUANG KARIER ALUMNI</span><h2>Siapkan profil.<br />Temukan peluang terbaik.</h2><p>Kelola profil profesional, susun CV, temukan lowongan, dan ikuti kegiatan alumni Farmasi UBP dalam satu tempat.</p><div className="auth-proof"><b>✓</b><span>Satu akun untuk seluruh layanan karier</span></div><div className="auth-proof"><b>✓</b><span>Anda menentukan data yang ingin dibagikan</span></div></div></aside>
    </main></>;
}
