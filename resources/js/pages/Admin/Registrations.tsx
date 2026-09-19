import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { StaffHeader } from '../../components/RoleHeader';
import { EmptyState } from '../../components/Ui';

type Item = { reference: string; full_name: string; student_number: string; status: string; created_at?: string };
type PageFeedback = { flash?: { success?: string }; errors?: Record<string, string> };

export default function Registrations({ registrations, activeStatus, loadError, canApprove }: { registrations: Item[]; activeStatus: string; loadError?: string | null; canApprove: boolean }) {
    const page = usePage<PageFeedback>().props;
    const [query, setQuery] = useState('');
    const [selected, setSelected] = useState<Set<string>>(new Set());
    const [processing, setProcessing] = useState(false);
    const filtered = useMemo(() => registrations.filter(item => `${item.full_name} ${item.student_number} ${item.reference}`.toLowerCase().includes(query.toLowerCase())), [registrations, query]);
    const bulkEnabled = canApprove && ['pending', 'manual_review'].includes(activeStatus);
    const selectableReferences = useMemo(() => bulkEnabled ? filtered.map(item => item.reference) : [], [bulkEnabled, filtered]);
    const allSelected = selectableReferences.length > 0 && selectableReferences.every(reference => selected.has(reference));
    const toggleAll = () => setSelected(current => {
        const next = new Set(current);
        if (allSelected) selectableReferences.forEach(reference => next.delete(reference));
        else selectableReferences.forEach(reference => next.add(reference));
        return next;
    });
    const toggleOne = (reference: string) => setSelected(current => {
        const next = new Set(current);
        if (next.has(reference)) next.delete(reference);
        else next.add(reference);
        return next;
    });
    const approveSelected = () => {
        if (selected.size === 0 || processing) return;
        setProcessing(true);
        router.post('/admin/registrations/bulk-approve', { references: [...selected] }, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setSelected(new Set());
            },
        });
    };
    return <><Head title="Verifikasi Alumni" /><main className="admin-page app-surface">
        <StaffHeader context="LAYANAN FARMASI" active="registrations" />
        <section className="admin-content"><div className="admin-page-heading"><div><p className="eyebrow">REGISTRASI ALUMNI</p><h1 className="page-title">Antrean verifikasi.</h1><p className="page-lead">Tinjau data minimum alumni dan ambil keputusan sesuai capability Anda.</p></div><div className="queue-summary"><small>STATUS AKTIF</small><strong>{activeStatus.replace('_', ' ')}</strong><span>{registrations.length} pendaftaran</span></div></div>
            <div className="queue-tools"><div className="filter-row">{['pending', 'manual_review', 'approved', 'rejected'].map((status) => <Link className={activeStatus === status ? 'active' : ''} href={`/admin/registrations?status=${status}`} key={status}>{status.replace('_', ' ')}</Link>)}</div><label className="search-field"><span aria-hidden="true">⌕</span><input value={query} onChange={event => { setQuery(event.target.value); setSelected(new Set()); }} placeholder="Cari nama, NIM, atau referensi" aria-label="Cari registrasi" /></label></div>
            {page.flash?.success && <div className="alert success">{page.flash.success}</div>}
            {page.errors?.bulk && <div className="alert error">{page.errors.bulk}</div>}
            {loadError && <div className="alert error"><strong>Data registrasi belum dapat dimuat.</strong><br />Layanan data alumni belum tersedia di lingkungan ini. Coba kembali setelah koneksi layanan aktif.</div>}
            {bulkEnabled && filtered.length > 0 && <div className="bulk-approval-bar"><label className="bulk-select-all"><input type="checkbox" checked={allSelected} onChange={toggleAll} /><span><strong>Pilih semua yang tampil</strong><small>{selected.size > 0 ? `${selected.size} pendaftar dipilih` : 'Pilih beberapa pendaftar untuk validasi sekaligus'}</small></span></label><button className="primary-button" type="button" disabled={selected.size === 0 || processing} onClick={approveSelected}>{processing ? 'Memvalidasi…' : selected.size > 0 ? `Setujui ${selected.size} pendaftar` : 'Pilih pendaftar'}</button></div>}
            <div className="registration-list">{filtered.length === 0 && <EmptyState icon="✓" title={query ? 'Tidak ada hasil pencarian.' : 'Antrean ini sudah bersih.'} description={query ? 'Coba nama, NIM, atau nomor referensi lain.' : 'Tidak ada pendaftaran pada status ini.'} />}
                {filtered.map((item) => <article className={`registration-row${bulkEnabled ? ' bulk-enabled' : ''}${selected.has(item.reference) ? ' selected' : ''}`} key={item.reference}>{bulkEnabled && <label className="registration-check" aria-label={`Pilih ${item.full_name}`}><input type="checkbox" checked={selected.has(item.reference)} onChange={() => toggleOne(item.reference)} /></label>}<span className="registration-person"><i>{item.full_name.slice(0, 2).toUpperCase()}</i><span><strong>{item.full_name}</strong><small>NIM {item.student_number}</small></span></span><code>{item.reference}</code><b>{item.status.replace('_', ' ')}</b><Link className="registration-review" href={`/admin/registrations/${item.reference}`}>Periksa →</Link></article>)}
            </div>
        </section>
    </main></>;
}
