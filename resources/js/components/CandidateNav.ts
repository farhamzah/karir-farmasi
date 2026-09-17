import type { NavItem } from './AppHeader';

type CandidateSection = 'dashboard' | 'profile' | 'cv' | 'events' | 'jobs' | 'tracer' | 'notifications';

export function candidateNav(active: CandidateSection, unread = 0): NavItem[] {
    return [
        { label: 'Beranda', href: '/dashboard', active: active === 'dashboard' },
        { label: 'Profil', href: '/profile', active: active === 'profile' },
        { label: 'Studio CV', href: '/cv', active: active === 'cv' },
        { label: 'Event', href: '/events', active: active === 'events' },
        { label: 'Lowongan', href: '/jobs', active: active === 'jobs' },
        { label: 'Tracer', href: '/tracer', active: active === 'tracer' },
        { label: `Inbox${unread > 0 ? ` (${unread})` : ''}`, href: '/notifications', active: active === 'notifications' },
    ];
}
