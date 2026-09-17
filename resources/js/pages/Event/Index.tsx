import { Head, Link } from '@inertiajs/react';
import AppHeader from '../../components/AppHeader';

export type EventData = { id:number;slug:string;title:string;event_type:string;organizer:string;description:string;starts_at:string;ends_at:string;location_type:string;location_text:string|null;topics:string[];capacity:number|null;registrations_count:number;registered:boolean;registration_id:number|null;registration_open:boolean };

export default function EventIndex({ events }: { events: EventData[] }) {
    return <><Head title="Event & Seminar" /><main className="event-page app-surface"><AppHeader context="EVENT & PORTOFOLIO" nav={[{label:'Dashboard',href:'/dashboard'},{label:'Jelajahi Event',href:'/events',active:true},{label:'Event Saya',href:'/events/mine'}]} logout />
        <section className="event-shell"><header className="event-hero"><div><p className="eyebrow">TUMBUH LEWAT PENGALAMAN</p><h1>Temukan ruang belajar,<br/><em>bangun jejak profesional.</em></h1><p>Ikuti seminar, pelatihan, dan kegiatan Farmasi UBP. Penyelesaian terverifikasi dapat Anda pilih sebagai bagian CV.</p></div><Link className="secondary-button" href="/events/mine">Lihat event saya</Link></header>
        <div className="event-grid">{events.map(event=><Link className="event-card" href={'/events/'+event.slug} key={event.id}><div className="event-card-top"><span className="event-type">{event.event_type}</span><span>{event.registered?'Terdaftar':'Buka'}</span></div><div className="event-icon">✦</div><h2>{event.title}</h2><p>{event.organizer}</p><dl><div><dt>Waktu</dt><dd>{event.starts_at}</dd></div><div><dt>Lokasi</dt><dd>{event.location_text||event.location_type}</dd></div></dl><div className="topic-row">{event.topics.map(topic=><span key={topic}>{topic}</span>)}</div><b>Lihat detail →</b></Link>)}</div>
        {events.length===0&&<div className="ds-empty-state"><span>✦</span><h2>Belum ada event aktif.</h2><p>Event yang telah dipublikasikan admin akan tampil di sini.</p></div>}</section></main></>;
}
