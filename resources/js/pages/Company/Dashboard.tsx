import { Head, Link } from '@inertiajs/react';
import { CompanyHeader } from '../../components/RoleHeader';

type Props = {
    user: { name: string; role: string };
    company: { name: string; sector: string; city: string; status: string; active: boolean; can_search: boolean };
    recruiterCount: number;
    shortlistCount: number;
};

export default function Dashboard({ user, company, recruiterCount, shortlistCount }: Props) {
    const canManageTeam = user.role === 'company_admin';

    return <><Head title="Dashboard Perusahaan" /><main className="admin-page app-surface company-workspace"><CompanyHeader context="MITRA PERUSAHAAN" active="dashboard" canSearch={company.can_search} canManageTeam={canManageTeam} />
        <section className="admin-content company-dashboard">
            <header className="company-command-hero">
                <div><p className="eyebrow">{company.sector} · {company.city}</p><h1>Rekrut talenta dengan<br /><em>konteks yang lebih utuh.</em></h1><p>Selamat datang, {user.name}. Kelola lowongan, tim rekrutmen, dan kandidat yang secara aktif memberi izin untuk ditemukan.</p><div className="company-command-actions">{company.can_search && <Link className="primary-button" href="/company/talent">Cari talenta</Link>}<Link className="company-hero-link" href="/company/jobs">Kelola lowongan →</Link></div></div>
                <aside><small>MITRA TERVERIFIKASI</small><strong>{company.name}</strong><span className={'company-status ' + company.status}>{company.status}</span><p>Ruang kerja privat untuk tim rekrutmen Anda.</p></aside>
            </header>

            {!company.can_search && <div className="verification-callout"><span aria-hidden="true">!</span><div><strong>Talent Search belum aktif</strong><p>Status perusahaan: {company.status}. Admin Karir perlu memverifikasi data perusahaan sebelum pencarian dibuka.</p></div></div>}

            <div className="company-overview-heading"><div><p className="eyebrow">RINGKASAN RUANG KERJA</p><h2>Aktivitas rekrutmen</h2></div><p>Setiap tindakan tercatat pada akun rekruter yang melakukannya.</p></div>
            <div className="overview-grid company-metric-grid"><article><span>REKRUTER AKTIF</span><strong>{recruiterCount}</strong><p>Setiap rekruter memakai akun sendiri.</p></article><article><span>SHORTLIST PRIVAT</span><strong>{shortlistCount}</strong><p>Kandidat tersimpan khusus perusahaan ini.</p></article><Link href="/company/jobs"><span>LOWONGAN</span><strong>Kelola</strong><p>Buat, tinjau, dan pantau pelamar.</p><b>Buka rekrutmen →</b></Link><Link href="/company/notifications"><span>PEMBARUAN</span><strong>Inbox</strong><p>Status kandidat dan aktivitas penting.</p><b>Buka inbox →</b></Link></div>

            <div className="dashboard-actions">{company.can_search && <Link className="primary-button" href="/company/talent">Buka Talent Search</Link>}{canManageTeam && <Link className="secondary-button" href="/company/recruiters">Kelola tim rekruter</Link>}</div>
        </section>
    </main></>;
}
