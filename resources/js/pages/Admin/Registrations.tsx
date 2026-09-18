import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { StaffHeader } from '../../components/RoleHeader';
import { EmptyState } from '../../components/Ui';

type Item = { reference: string; full_name: string; student_number: string; status: string; created_at?: string };

export default function Registrations({ registrations, activeStatus, loadError }: { registrations: Item[]; activeStatus: string; loadError?: string | null }) {
    const [query, setQuery] = useState('');
    const filtered = useMemo(() => registrations.filter(item => `${item.full_name} ${item.student_number} ${item.reference}`.toLowerCase().includes(query.toLowerCase())), [registrations, query]);
    return <><Head title="Verifikasi Alumni" /><main className="admin-page app-surface">
        <StaffHeader context="LAYANAN FARMASI" active="registrations" />
        <section className="admin-content"><div className="admin-page-heading"><div><p className="eyebrow">REGISTRASI ALUMNI</p><h1 className="page-title">Antrean verifikasi.</h1><p className="page-lead">Tinjau data minimum alumni dan ambil keputusan sesuai capability Anda.</p></div><div className="queue-summary"><small>STATUS AKTIF</small><strong>{activeStatus.replace('_', ' ')}</strong><span>{registrations.length} pendaftaran</span></div></div>
            <div className="queue-tools"><div className="filter-row">{['pending', 'manual_review', 'approved', 'rejected'].map((status) => <Link className={activeStatus === status ? 'active' : ''} href={`/admin/registrations?status=${status}`} key={status}>{status.replace('_', ' ')}</Link>)}</div><label className="search-field"><span aria-hidden="true">⌕</span><input value={query} onChange={event => setQuery(event.target.value)} placeholder="Cari nama, NIM, atau referensi" aria-label="Cari registrasi" /></label></div>
            {loadError && <div className="alert error"><strong>Data registrasi belum dapat dimuat.</strong><br />Layanan data alumni belum tersedia di lingkungan ini. Coba kembali setelah koneksi layanan aktif.</div>}
            <div className="registration-list">{filtered.length === 0 && <EmptyState icon="✓" title={query ? 'Tidak ada hasil pencarian.' : 'Antrean ini sudah bersih.'} description={query ? 'Coba nama, NIM, atau nomor referensi lain.' : 'Tidak ada pendaftaran pada status ini.'} />}
                {filtered.map((item) => <Link className="registration-row" href={`/admin/registrations/${item.reference}`} key={item.reference}><span className="registration-person"><i>{item.full_name.slice(0, 2).toUpperCase()}</i><span><strong>{item.full_name}</strong><small>NIM {item.student_number}</small></span></span><code>{item.reference}</code><b>{item.status.replace('_', ' ')}</b><em>Periksa →</em></Link>)}
            </div>
        </section>
    </main></>;
}
