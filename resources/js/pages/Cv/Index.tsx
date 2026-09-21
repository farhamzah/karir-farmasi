import { Head, Link, router, usePage } from '@inertiajs/react';
import AppHeader from '../../components/AppHeader';
import { candidateNav } from '../../components/CandidateNav';
import { EmptyState } from '../../components/Ui';

type Cv = { id: number; name: string; status: string; template: string; template_key: string; version: string; updated_at: string; share_url: string | null };

export default function CvIndex({ profileReady, cvs }: { profileReady: boolean; cvs: Cv[] }) {
    const flash = usePage<{ flash?: { success?: string } }>().props.flash;
    return <><Head title="CV Saya" /><main className="cv-page app-surface"><AppHeader context="STUDIO CV" nav={candidateNav('cv')} logout />
        <section className="cv-shell"><div className="cv-title-row"><div><p className="eyebrow">CV PROFESIONAL</p><h1 className="page-title">Satu profil,<br /><em>banyak cerita karier.</em></h1><p className="page-lead">Susun CV untuk setiap peluang tanpa menulis ulang profil Anda. Pilih informasi yang relevan, atur urutannya, lalu lihat hasilnya seketika.</p></div>{profileReady && <Link className="primary-button" href="/cv/create">Buat CV baru</Link>}</div>
        {flash?.success && <div className="alert success">{flash.success}</div>}
        {!profileReady ? <EmptyState icon="01" title="Mulai dari profil profesional." description="Isi profil sekali, lalu gunakan datanya untuk berbagai versi CV." action={{ label: 'Lengkapi profil', href: '/profile/edit' }} /> : cvs.length === 0 ? <EmptyState icon="CV" title="Siap merangkai langkah berikutnya?" description="Buat CV pertama dengan tampilan yang jelas dan membawa kekuatan pengalaman Anda." action={{ label: 'Buat CV pertama', href: '/cv/create' }} /> : <div className="cv-grid">{cvs.map(cv => <article className="cv-card cv-portfolio-card" key={cv.id}><div className={`cv-card-cover ${cv.template_key}`}><span>Curriculum Vitae</span><i /><b /><em>{cv.template}</em></div><div className="cv-card-copy"><span className={`cv-status ${cv.status}`}>{cv.status === 'active' ? 'Aktif' : 'Draf'}</span><small>{cv.template} · v{cv.version}</small><h2>{cv.name}</h2><p>Diperbarui {new Date(cv.updated_at).toLocaleDateString('id-ID')}</p>{cv.share_url && <p className="cv-published-link">Tautan publik tersimpan · siap dibagikan</p>}</div><div className="cv-card-actions"><Link className="card-primary-action" href={`/cv/${cv.id}/preview`}>Lihat preview</Link>{cv.share_url && <><button onClick={() => navigator.clipboard.writeText(cv.share_url!)}>Salin tautan</button><a href={cv.share_url} target="_blank" rel="noreferrer">Buka CV publik</a></>}<Link href={`/cv/${cv.id}/edit`}>Atur isi</Link><button onClick={() => router.post(`/cv/${cv.id}/duplicate`)}>Duplikat</button><button className="danger-text" onClick={() => confirm(`Hapus ${cv.name}? Profil profesional tetap aman.`) && router.delete(`/cv/${cv.id}`)}>Hapus</button></div></article>)}</div>}
        </section></main></>;
}
