import { Head, useForm, usePage } from '@inertiajs/react';
import AppHeader from '../../components/AppHeader';
import type { FormEvent } from 'react';

type Registration = Record<string, string | number | null>;

export default function RegistrationDetail({ registration, canApprove, canReject }: { registration: Registration; canApprove: boolean; canReject: boolean }) {
    const page = usePage<{ auth?: { actor?: { roles: string[] } }; flash?: { success?: string }; errors?: Record<string, string> }>();
    const reject = useForm({ reason: '' });
    const approve = useForm({});
    const pending = registration.status === 'pending';
    const rejectSubmit = (event: FormEvent) => { event.preventDefault(); reject.post(`/admin/registrations/${registration.reference}/reject`); };

    const staffLabel = page.props.auth?.actor?.roles.includes('admin-karir') ? 'Admin Karir' : 'Petugas Karir';

    return <><Head title="Detail Verifikasi" /><main className="admin-page app-surface"><AppHeader context="DETAIL VERIFIKASI" home="/staff" nav={[{ label: '← Antrean', href: '/admin/registrations' }, { label: 'Overview', href: '/staff' }]} actions={<span className="status-dot">{staffLabel}</span>} />
        <section className="admin-content detail-layout"><div><p className="eyebrow">DETAIL PENDAFTARAN</p><h1 className="page-title">{registration.full_name}</h1><code>{registration.reference}</code>
            {page.props.flash?.success && <div className="alert success">{page.props.flash.success}</div>}
            {page.props.errors?.decision && <div className="alert error">{page.props.errors.decision}</div>}
            <dl className="detail-list">{Object.entries(registration).filter(([key]) => !['full_name', 'reference', 'review_note'].includes(key)).map(([key, value]) => <div key={key}><dt>{key.replaceAll('_', ' ')}</dt><dd>{value == null ? '—' : String(value)}</dd></div>)}</dl>
        </div>{pending && (canApprove || canReject) && <aside className="decision-card"><h2>Keputusan admin</h2><p>Persetujuan memberi akses kandidat. Tidak ada OTP atau kewajiban melengkapi profil Core.</p>
            {canApprove && <button className="primary-button" disabled={approve.processing} onClick={() => approve.post(`/admin/registrations/${registration.reference}/approve`)}>Setujui alumni</button>}
            {canReject && <form onSubmit={rejectSubmit}><label>Alasan penolakan<textarea value={reject.data.reason} onChange={(event) => reject.setData('reason', event.target.value)} required minLength={3} /></label>{reject.errors.reason && <p className="field-error">{reject.errors.reason}</p>}<button className="danger-button" disabled={reject.processing}>Tolak pendaftaran</button></form>}
        </aside>}{pending && !canApprove && !canReject && <aside className="decision-card read-only-card"><h2>Mode petugas</h2><p>Anda dapat meninjau detail pendaftaran. Keputusan approve/reject hanya tersedia untuk admin.</p></aside>}</section>
    </main></>;
}
