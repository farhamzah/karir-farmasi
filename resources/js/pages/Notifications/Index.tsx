import { Head, Link, router } from '@inertiajs/react';
import AppHeader from '../../components/AppHeader';

type Item = { reference:string; type:string; title:string; body:string; action_url:string|null; read:boolean; created_at:string };

export default function Index({ notifications, home, audience }:{ notifications:Item[]; home:string; audience:'candidate'|'company_user' }) {
    const read = (reference:string) => router.put(`${audience === 'candidate' ? '' : '/company'}/notifications/${reference}/read`);
    return <><Head title="Inbox"/><main className="app-surface notification-page">
        <AppHeader context="INBOX KARIER" home={home} nav={[{label:'← Dashboard',href:home}]}/>
        <section className="operational-shell"><header className="operational-hero"><div><p className="eyebrow">PEMBARUAN PENTING</p><h1>Semua kabar karier<br/><em>dalam satu inbox.</em></h1><p>Undangan, progres lamaran, sertifikat, dan keputusan akun tampil di sini tanpa email eksternal.</p></div><span className="metric-orb">{notifications.filter(item=>!item.read).length}<small>belum dibaca</small></span></header>
            <div className="notification-list">{notifications.map(item=><article key={item.reference} className={item.read?'is-read':''}><span className="notification-dot"/><div><small>{item.type.replaceAll('.',' · ')} · {item.created_at}</small><h2>{item.title}</h2><p>{item.body}</p>{item.action_url&&<Link href={item.action_url}>Buka detail →</Link>}</div>{!item.read&&<button className="secondary-button" onClick={()=>read(item.reference)}>Tandai dibaca</button>}</article>)}</div>
            {!notifications.length&&<div className="ds-empty-state"><span>✦</span><h2>Inbox masih tenang.</h2><p>Pembaruan penting akan tampil otomatis.</p></div>}
        </section>
    </main></>;
}
