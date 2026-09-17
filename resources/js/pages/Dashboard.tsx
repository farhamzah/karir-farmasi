import { Head, Link } from '@inertiajs/react';
import AppHeader from '../components/AppHeader';
import { candidateNav } from '../components/CandidateNav';

type Principal = { display_name: string; email: string | null; roles: string[]; capabilities: string[] };

type Operations = { tracer_status?: string; notifications_unread?: number; tracer_open: number; tracer_submitted: number };

export default function Dashboard({ principal, operations }: { principal: Principal; operations: Operations }) {
    const unreadNotifications = operations.notifications_unread ?? 0;
    const tracerProgress = operations.tracer_open > 0 ? `${operations.tracer_submitted}/${operations.tracer_open}` : 'Siap';

    return (
        <>
            <Head title="Dashboard Kandidat" />
            <main className="dashboard-page app-surface">
                <AppHeader context="RUANG KANDIDAT" nav={candidateNav('dashboard', unreadNotifications)} logout />

                <section className="dashboard-hero dashboard-hero-redesign">
                    <div className="dashboard-hero-copy">
                        <p className="eyebrow">RUANG KANDIDAT</p>
                        <h1>
                            Selamat datang,
                            <br />
                            <em>{principal.display_name}</em>.
                        </h1>
                        <p>
                            Rapikan profil, pilih cerita karier terbaik, ikuti event, dan gunakan CV yang siap dibagikan
                            saat peluang datang.
                        </p>
                        <div className="hero-actions">
                            <Link className="primary-button" href="/profile">
                                Lengkapi profil
                            </Link>
                            <Link className="secondary-button" href="/cv">
                                Buka Studio CV
                            </Link>
                        </div>
                    </div>

                    <aside className="dashboard-command-panel" aria-label="Ringkasan ruang kandidat">
                        <div className="identity-card">
                            <span className="identity-card-icon">✓</span>
                            <div>
                                <small>IDENTITAS TERVERIFIKASI</small>
                                <strong>{principal.email || 'Email tidak dibagikan Core'}</strong>
                                <p>Data akun tetap dikelola melalui Core. Profil profesional Anda berada di SAFA KARIR.</p>
                            </div>
                        </div>

                        <div className="dashboard-mini-grid">
                            <Link href="/profile">
                                <span>Profil</span>
                                <strong>Lengkapkan</strong>
                                <small>Sumber data CV</small>
                            </Link>
                            <Link href="/cv">
                                <span>Studio CV</span>
                                <strong>Template</strong>
                                <small>Preview & bagikan</small>
                            </Link>
                            <Link href="/events">
                                <span>Event</span>
                                <strong>Sertifikat</strong>
                                <small>Portofolio terstruktur</small>
                            </Link>
                            <Link href="/notifications">
                                <span>Inbox</span>
                                <strong>{unreadNotifications}</strong>
                                <small>Notifikasi baru</small>
                            </Link>
                        </div>

                        <div className="dashboard-next-card">
                            <span>LANGKAH BERIKUTNYA</span>
                            <h2>Jadikan profil Anda siap dilihat recruiter.</h2>
                            <p>
                                Cek lowongan, lengkapi sertifikat, lalu publikasikan CV hanya ketika Anda sudah siap.
                            </p>
                            <div>
                                <Link href="/jobs">Lihat lowongan</Link>
                                <Link href="/tracer">Tracer {tracerProgress}</Link>
                            </div>
                        </div>
                    </aside>
                </section>

                <section className="dashboard-content dashboard-content-redesign">
                    <div className="section-heading-row">
                        <div>
                            <p className="eyebrow">AKSI UTAMA</p>
                            <h2>Teruskan progres Anda.</h2>
                        </div>
                        <span className="privacy-pill">Privat sampai Anda publikasikan</span>
                    </div>

                    <div className="action-grid">
                        <Link className="action-card featured" href="/profile">
                            <span>01</span>
                            <div>
                                <h3>Profil profesional</h3>
                                <p>Rawat satu sumber data untuk pendidikan, pengalaman, kompetensi, dan karya.</p>
                            </div>
                            <b>Lihat profil →</b>
                        </Link>
                        <Link className="action-card" href="/cv">
                            <span>02</span>
                            <div>
                                <h3>Studio CV</h3>
                                <p>Susun beberapa CV, ganti template, dan pilih informasi sesuai tujuan.</p>
                            </div>
                            <b>Kelola CV →</b>
                        </Link>
                        <Link className="action-card" href="/jobs">
                            <span>03</span>
                            <div>
                                <h3>Lowongan farmasi</h3>
                                <p>Temukan peluang dari perusahaan terverifikasi dan sumber kampus.</p>
                            </div>
                            <b>Cari lowongan →</b>
                        </Link>
                        <Link className="action-card" href="/events">
                            <span>04</span>
                            <div>
                                <h3>Event & sertifikat</h3>
                                <p>Ikuti kegiatan dan simpan bukti kompetensi untuk portofolio.</p>
                            </div>
                            <b>Buka event →</b>
                        </Link>
                        {principal.capabilities.includes('aggregate.dashboard.view') && (
                            <Link className="action-card" href="/staff">
                                <span>05</span>
                                <div>
                                    <h3>Overview layanan</h3>
                                    <p>Buka ruang operasional sesuai capability yang diberikan server.</p>
                                </div>
                                <b>Buka overview →</b>
                            </Link>
                        )}
                    </div>

                    <div className="future-strip" id="future">
                        <div>
                            <p className="eyebrow">TUMBUH LEWAT PENGALAMAN</p>
                            <h2>Event, lowongan, dan tracer melengkapi perjalanan alumni.</h2>
                        </div>
                        <div className="future-grid compact">
                            <Link className="coming-soon-card" href="/events">
                                <span className="feature-icon">✦</span>
                                <div>
                                    <span className="soon-label">TERSEDIA</span>
                                    <h3>Event & Seminar</h3>
                                    <p>Kegiatan dan sertifikat terverifikasi untuk portofolio Anda.</p>
                                </div>
                            </Link>
                            <Link className="coming-soon-card" href="/tracer">
                                <span className="feature-icon">◉</span>
                                <div>
                                    <span className="soon-label">{tracerProgress} TERISI</span>
                                    <h3>Tracer Study</h3>
                                    <p>Ceritakan transisi karier dengan data yang jujur dan kontekstual.</p>
                                </div>
                            </Link>
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}
