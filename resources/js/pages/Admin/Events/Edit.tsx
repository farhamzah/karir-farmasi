import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { StaffHeader } from '../../../components/RoleHeader';

type EventForm = {
    id?: number;
    title: string;
    event_type: string;
    organizer: string;
    description: string;
    starts_at: string;
    ends_at: string;
    location_type: string;
    location_text: string;
    capacity: string;
    registration_opens_at: string;
    registration_closes_at: string;
    registration_notes: string;
    certificate_enabled: boolean;
    topics: string[];
    has_flyer?: boolean;
    flyer_url?: string | null;
    flyer_alt_text?: string;
};

export default function Edit({ event }: { event: EventForm | null }) {
    const form = useForm({
        title: event?.title ?? '',
        event_type: event?.event_type ?? 'seminar',
        organizer: event?.organizer ?? 'Program Studi Farmasi UBP',
        description: event?.description ?? '',
        flyer: null as File | null,
        flyer_alt_text: event?.flyer_alt_text ?? '',
        remove_flyer: false,
        starts_at: event?.starts_at?.slice(0, 16) ?? '',
        ends_at: event?.ends_at?.slice(0, 16) ?? '',
        location_type: event?.location_type ?? 'onsite',
        location_text: event?.location_text ?? '',
        capacity: event?.capacity ?? '',
        registration_opens_at: event?.registration_opens_at?.slice(0, 16) ?? '',
        registration_closes_at: event?.registration_closes_at?.slice(0, 16) ?? '',
        registration_notes: event?.registration_notes ?? '',
        certificate_enabled: event?.certificate_enabled ?? true,
        topics: (event?.topics ?? []).join(', '),
    });

    const submit = (submitEvent: FormEvent) => {
        submitEvent.preventDefault();
        form.transform((data) => ({
            ...data,
            capacity: data.capacity ? Number(data.capacity) : null,
            topics: data.topics.split(',').map((topic) => topic.trim()).filter(Boolean),
            ...(event?.id ? { _method: 'put' } : {}),
        }));
        form.post(event?.id ? `/admin/events/${event.id}` : '/admin/events', { forceFormData: true });
    };

    return <><Head title={event ? 'Edit Event' : 'Buat Event'} /><main className="admin-page app-surface">
        <StaffHeader context="EDITOR EVENT" active="events" />
        <section className="admin-content event-shell">
            <header className="event-list-heading"><div><p className="eyebrow">PUBLIKASI KEGIATAN</p><h1>{event ? 'Edit event' : 'Buat event baru'}.</h1><p>Lengkapi informasi yang dibutuhkan alumni sebelum mendaftar. Event baru selalu disimpan sebagai draft.</p></div></header>
            <form className="profile-form-card event-form" onSubmit={submit}>
                <div className="form-grid">
                    <label>Judul<input value={form.data.title} onChange={(changeEvent) => form.setData('title', changeEvent.target.value)} required /></label>
                    <label>Jenis<select value={form.data.event_type} onChange={(changeEvent) => form.setData('event_type', changeEvent.target.value)}>{['seminar', 'workshop', 'webinar', 'pelatihan', 'bootcamp', 'kuliah_tamu', 'career_event'].map((type) => <option key={type} value={type}>{type.replace('_', ' ')}</option>)}</select></label>
                    <label>Penyelenggara<input value={form.data.organizer} onChange={(changeEvent) => form.setData('organizer', changeEvent.target.value)} required /></label>
                    <label>Topik terstruktur<input value={form.data.topics} onChange={(changeEvent) => form.setData('topics', changeEvent.target.value)} placeholder="CPOB, QA/QC, Patient safety" required /><small>Pisahkan setiap topik dengan koma.</small></label>
                    <label>Mulai<input type="datetime-local" value={form.data.starts_at} onChange={(changeEvent) => form.setData('starts_at', changeEvent.target.value)} required /></label>
                    <label>Selesai<input type="datetime-local" value={form.data.ends_at} onChange={(changeEvent) => form.setData('ends_at', changeEvent.target.value)} required /></label>
                    <label>Tipe lokasi<select value={form.data.location_type} onChange={(changeEvent) => form.setData('location_type', changeEvent.target.value)}><option value="onsite">Onsite</option><option value="online">Online</option><option value="hybrid">Hybrid</option></select></label>
                    <label>Lokasi atau platform<input value={form.data.location_text} onChange={(changeEvent) => form.setData('location_text', changeEvent.target.value)} placeholder="Kampus UBP / Zoom" /></label>
                    <label>Kapasitas<input type="number" min="1" value={form.data.capacity} onChange={(changeEvent) => form.setData('capacity', changeEvent.target.value)} placeholder="Kosongkan jika tanpa batas" /></label>
                    <label>Buka pendaftaran<input type="datetime-local" value={form.data.registration_opens_at} onChange={(changeEvent) => form.setData('registration_opens_at', changeEvent.target.value)} /></label>
                    <label>Tutup pendaftaran<input type="datetime-local" value={form.data.registration_closes_at} onChange={(changeEvent) => form.setData('registration_closes_at', changeEvent.target.value)} /></label>
                    <label className="form-wide">Deskripsi<textarea value={form.data.description} onChange={(changeEvent) => form.setData('description', changeEvent.target.value)} placeholder="Jelaskan tujuan, manfaat, sasaran peserta, dan rangkaian kegiatan." required /></label>
                    <label className="form-wide">Catatan pendaftaran<textarea value={form.data.registration_notes} onChange={(changeEvent) => form.setData('registration_notes', changeEvent.target.value)} placeholder="Contoh: peserta wajib membawa laptop atau bergabung 15 menit sebelum acara." /></label>
                    <label className="form-wide event-flyer-input">Flyer kegiatan<input type="file" accept="image/jpeg,image/png,image/webp" onChange={(changeEvent) => form.setData('flyer', changeEvent.target.files?.[0] ?? null)} /><small>JPG, PNG, atau WebP, maksimal 5 MB. Flyer tampil setelah event dipublikasikan.</small></label>
                    <label className="form-wide">Deskripsi gambar flyer<input value={form.data.flyer_alt_text} onChange={(changeEvent) => form.setData('flyer_alt_text', changeEvent.target.value)} placeholder="Flyer Seminar Nasional Farmasi Klinis" /></label>
                    {event?.has_flyer && <label className="check-row"><input type="checkbox" checked={form.data.remove_flyer} onChange={(changeEvent) => form.setData('remove_flyer', changeEvent.target.checked)} /> Hapus flyer yang tersimpan</label>}
                    {event?.flyer_url && !form.data.remove_flyer && <img className="event-flyer-preview" src={event.flyer_url} alt={event.flyer_alt_text || `Flyer ${event.title}`} />}
                    <label className="check-row"><input type="checkbox" checked={form.data.certificate_enabled} onChange={(changeEvent) => form.setData('certificate_enabled', changeEvent.target.checked)} /> Terbitkan sertifikat untuk peserta yang dinyatakan selesai</label>
                </div>
                {Object.keys(form.errors).length > 0 && <div className="alert error"><strong>Periksa kembali data event.</strong>{Object.values(form.errors).map((error) => <span key={error}>{error}</span>)}</div>}
                <button className="primary-button" disabled={form.processing}>{form.processing ? 'Menyimpan…' : 'Simpan event'}</button>
            </form>
        </section>
    </main></>;
}
