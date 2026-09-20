import { Link, usePage } from '@inertiajs/react';
import AppHeader from './AppHeader';
import { candidateNav } from './CandidateNav';

type Actor = { roles: string[] };

export default function OpportunityHeader({ context, active, canInteract }: { context: string; active: 'jobs' | 'events'; canInteract: boolean }) {
    const actor = usePage<{ auth?: { actor?: Actor } }>().props.auth?.actor;

    if (canInteract) {
        return <AppHeader context={context} nav={candidateNav(active)} logout />;
    }

    const accountHref = actor ? '/staff' : '/login';
    const accountLabel = actor ? 'Ruang pengelola' : 'Masuk';

    return <AppHeader
        context={context}
        home="/"
        verified={false}
        nav={[
            { label: 'Beranda', href: '/' },
            { label: 'Lowongan', href: '/jobs', active: active === 'jobs' },
            { label: 'Event', href: '/events', active: active === 'events' },
        ]}
        actions={<Link className="secondary-button" href={accountHref}>{accountLabel}</Link>}
    />;
}
