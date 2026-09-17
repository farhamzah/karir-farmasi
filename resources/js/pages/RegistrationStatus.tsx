import { Head, Link } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Brand from '../components/Brand';

type Registration = {
    reference: string;
    full_name?: string;
    status: 'pending' | 'approved' | 'rejected';
    account_resolution?: 'existing_core_user' | 'minimal_core_user' | null;
    review_note?: string | null;
};

export default function RegistrationStatus({ registration, lookupReference = '', lookupError, coreRecoveryUrl, decisionNotice }: {
    registration: Registration | null;
    lookupReference?: string;
    lookupError?: string | null;
    coreRecoveryUrl?: string | null;
    decisionNotice?: string | null;
}) {
    const [reference, setReference] = useState(lookupReference);
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (reference.trim()) window.location.assign(`/status/${encodeURIComponent(reference.trim())}`);
    };
    const approved = registration?.status === 'approved';

    return <><Head title="Status Pendaftaran" /><main className="form-page app-surface">
        <header className="compact-header app-header"><Brand /><Link className="ghost-button" href="/login">Masuk</Link></header>
        <section className="form-card status-card"><div className="status-intro-icon">⌁</div><p className="eyebrow">STATUS PENDAFTARAN</p><h1 className="page-title">Pantau langkah<br /><em>menuju ruang karier.</em></h1><p className="page-lead">Masukkan nomor referensi yang diterima setelah pendaftaran.</p>
            <form className="lookup-form" onSubmit={submit}><input aria-label="Nomor referensi" placeholder="Contoh: KARIR-..." value={reference} onChange={(event) => setReference(event.target.value)} required /><button className="secondary-button">Periksa</button></form>
            {lookupError && <div className="alert error">{lookupError}</div>}
            {decisionNotice && <div className="notice" role="status">{decisionNotice}</div>}
            {registration && <div className={`status-result ${registration.status}`}>
                <span className="status-label">{registration.status === 'pending' ? 'MENUNGGU ADMIN' : registration.status === 'approved' ? 'DISETUJUI' : 'DITOLAK'}</span>
                <h2>{registration.full_name || 'Pendaftaran alumni'}</h2><code>{registration.reference}</code>
                {registration.status === 'pending' && <p>Data Anda sudah diterima. Admin Farmasi UBP akan melakukan verifikasi.</p>}
                {approved && <><p>Akses SAFA KARIR sudah aktif. Silakan masuk ke dashboard kandidat.</p>
                    {registration.account_resolution === 'existing_core_user' && <p className="notice">Akun Core lama ditemukan. Gunakan password Core lama; password Anda tidak diubah.</p>}
                    <Link className="primary-button inline-button" href="/login">Masuk ke Karir</Link>
                    {coreRecoveryUrl && <a className="text-link" href={coreRecoveryUrl}>Lupa password Core</a>}
                </>}
                {registration.status === 'rejected' && <p>{registration.review_note || 'Pendaftaran belum dapat disetujui.'}</p>}
            </div>}
        </section>
    </main></>;
}
