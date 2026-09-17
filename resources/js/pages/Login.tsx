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
            <div className="auth-copy"><p className="eyebrow">SELAMAT DATANG KEMBALI</p><h1 className="page-title">Lanjutkan langkah<br /><em>profesional Anda.</em></h1>
            <p className="page-lead">Masuk dengan email, username, atau NIM dan password SAFA/Core Anda.</p></div>
            <form className="form-stack" onSubmit={submit}>
                <label>Identitas <small>Email, username, atau NIM</small><input value={form.data.identifier} onChange={(event) => form.setData('identifier', event.target.value)} autoComplete="username" placeholder="Masukkan identitas Anda" required /></label>
                {form.errors.identifier && <p className="field-error">{form.errors.identifier}</p>}
                <label>Password<input type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} autoComplete="current-password" placeholder="Masukkan password" required /></label>
                <button className="primary-button" disabled={form.processing}>Masuk ke Karir</button>
            </form>
            <div className="auth-links"><Link href="/register">Belum terdaftar? Daftar alumni</Link><Link href="/status">Cek status pendaftaran</Link>{coreRecoveryUrl && <a href={coreRecoveryUrl}>Lupa password Core</a>}</div>
        </section>
        <aside className="auth-aside"><div className="auth-aside-art"><span className="auth-orbit">SK</span><i /><i /><i /></div><div><span>IDENTITAS TERHUBUNG</span><h2>Satu akses,<br />ruang tumbuh yang lebih luas.</h2><p>Profil dan CV profesional Anda tersimpan di SAFA KARIR. Identitas akun tetap diverifikasi melalui Core.</p><div className="auth-proof"><b>✓</b><span>Privasi dalam kendali Anda</span></div><div className="auth-proof"><b>✓</b><span>Tidak perlu memperbarui profil Core</span></div></div></aside>
    </main></>;
}
