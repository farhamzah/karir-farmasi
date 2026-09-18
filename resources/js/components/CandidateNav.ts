import type { NavItem } from './AppHeader';

type CandidateSection = 'dashboard' | 'profile' | 'alumni' | 'cv' | 'events' | 'my-events' | 'jobs' | 'applications' | 'invitations' | 'tracer' | 'notifications';

export function candidateNav(active: CandidateSection, unread = 0): NavItem[] {
    return [
        { label: 'Beranda', href: '/dashboard', active: active === 'dashboard' },
        { label: 'Profil', href: '/profile', active: active === 'profile' },
        { label: 'CV Saya', href: '/cv', active: active === 'cv' },
        { label: 'Peluang', active: ['jobs', 'applications', 'invitations', 'events', 'my-events'].includes(active), children: [
            { label: 'Cari Lowongan', href: '/jobs', active: active === 'jobs' },
            { label: 'Lamaran Saya', href: '/jobs/applications', active: active === 'applications' },
            { label: 'Undangan', href: '/jobs/invitations', active: active === 'invitations' },
            { label: 'Jelajahi Event', href: '/events', active: active === 'events' },
            { label: 'Event Saya', href: '/events/mine', active: active === 'my-events' },
        ] },
        { label: 'Alumni', href: '/alumni', active: active === 'alumni' },
        { label: 'Tracer', href: '/tracer', active: active === 'tracer' },
        { label: `Inbox${unread > 0 ? ` (${unread})` : ''}`, href: '/notifications', active: active === 'notifications' },
    ];
}
