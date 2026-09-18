import { Head, Link, router } from '@inertiajs/react';
import Brand from '../components/Brand';

type RoleOption = {
    slug: string;
    label: string;
    eyebrow: string;
    description: string;
};

export default function RoleSelection({ displayName, roles, activeRole }: {
    displayName: string;
    roles: RoleOption[];
    activeRole: string | null;
}) {
    const choose = (role: string) => router.post('/pilih-role', { role });

    return <>
        <Head title="Pilih Ruang Kerja" />
        <main className="role-selection-page">
            <header className="role-selection-header">
                <Brand href="/" context="AKSES TERHUBUNG" />
                <button className="ghost-button" type="button" onClick={() => router.delete('/internal/session')}>Keluar</button>
            </header>
            <section className="role-selection-shell">
                <div className="role-selection-intro">
                    <p className="eyebrow">SELAMAT DATANG, {displayName.toUpperCase()}</p>
                    <h1>Pilih ruang kerja<br /><em>yang ingin dibuka.</em></h1>
                    <p>Akun Anda memiliki beberapa peran. Setiap ruang menampilkan menu dan kewenangan yang sesuai.</p>
                </div>
                <div className="role-option-grid">
                    {roles.map((role, index) => <button
                        className={`role-option-card${activeRole === role.slug ? ' is-active' : ''}`}
                        key={role.slug}
                        type="button"
                        onClick={() => choose(role.slug)}
                    >
                        <span className="role-option-number">{String(index + 1).padStart(2, '0')}</span>
                        <small>{role.eyebrow}</small>
                        <h2>{role.label}</h2>
                        <p>{role.description}</p>
                        <b>{activeRole === role.slug ? 'Ruang aktif' : 'Masuk ke ruang ini'} <span aria-hidden="true">→</span></b>
                    </button>)}
                </div>
                <footer className="role-selection-note">
                    <span aria-hidden="true">✓</span>
                    <p><strong>Hak akses tetap berasal dari Core.</strong> Pilihan ini hanya menentukan ruang kerja aktif dan dapat diganti kembali melalui header.</p>
                    <Link href="/">Kembali ke beranda</Link>
                </footer>
            </section>
        </main>
    </>;
}
