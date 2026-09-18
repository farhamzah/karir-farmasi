import { Form, Head } from '@inertiajs/react';
import AppHeader from '../../components/AppHeader';
import { candidateNav } from '../../components/CandidateNav';

type Question = { id:string; label:string; type:'text'|'textarea'|'select'|'number'|'date'; required:boolean; options?:string[] };
type Submission = { status:string; answers:Record<string,string|number>; prefill:Record<string,string|number> };

export default function Show({ period, version, questions, submission }:{ period:{reference:string;title:string;cohort:string}; version:number; questions:Question[]; submission:Submission }) {
    const locked = submission.status === 'submitted';
    return <><Head title={period.title}/><main className="app-surface tracer-page">
        <AppHeader context="TRACER STUDY" nav={candidateNav('tracer')} logout/>
        <section className="operational-shell narrow">
            <header className="form-hero"><p className="eyebrow">ANGKATAN {period.cohort} · VERSI {version}</p><h1>{period.title}</h1><p>Data profil yang jelas hanya dipakai sebagai prefill. Respons terkirim menjadi snapshot dan tidak berubah saat profil diedit.</p></header>
            <Form action={'/tracer/' + period.reference} method="put" className="tracer-form">
                {({processing})=><>
                    {Object.keys(submission.prefill).length > 0 && <aside className="prefill-card"><strong>Prefill dari profil</strong>{Object.entries(submission.prefill).map(([key,value])=><span key={key}>{key.replaceAll('_',' ')}: {value}</span>)}</aside>}
                    {questions.map(question=><label key={question.id}>{question.label}{question.required&&<b>*</b>}
                        {question.type==='textarea' ? <textarea name={'answers['+question.id+']'} defaultValue={submission.answers[question.id] ?? ''} disabled={locked}/> :
                         question.type==='select' ? <select name={'answers['+question.id+']'} defaultValue={submission.answers[question.id] ?? ''} disabled={locked}><option value="">Belum diketahui / pilih</option>{(question.options??[]).map(option=><option key={option}>{option}</option>)}</select> :
                         <input type={question.type} name={'answers['+question.id+']'} defaultValue={submission.answers[question.id] ?? ''} disabled={locked}/>}
                    </label>)}
                    {locked ? <div className="locked-banner">✓ Respons terkirim dan terkunci. Admin harus membuka revisi secara eksplisit.</div> :
                    <div className="form-actions"><button name="action" value="draft" className="secondary-button" disabled={processing}>Simpan draf</button><button name="action" value="submit" className="primary-button" disabled={processing}>Kirim respons</button></div>}
                </>}
            </Form>
        </section>
    </main></>;
}
