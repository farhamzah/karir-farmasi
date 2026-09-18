import { Head } from '@inertiajs/react';
import { StaffHeader } from '../../../components/RoleHeader';
import JobForm, { type Existing } from '../../../components/JobForm';
export default function AdminJobEdit({job}:{job:Existing|null}){return <><Head title={job?'Edit Lowongan':'Tambah Lowongan'}/><main className="admin-page"><StaffHeader context="TAMBAH LOWONGAN" active="jobs" /><section className="job-shell narrow"><p className="eyebrow">SATU FORM UNTUK DUA BENTUK INFORMASI</p><h1>{job?'Perbarui lowongan':'Tulis informasi atau unggah flyer.'}</h1><p className="page-lead">Gunakan keterangan untuk pengumuman berbentuk teks, flyer untuk poster, atau keduanya. Lowongan disimpan sebagai draft sebelum tampil kepada alumni.</p><JobForm job={job} audience="admin"/></section></main></>}
