import { usePage } from '@inertiajs/react';
import type { NavItem } from './AppHeader';

type StaffSection = 'overview' | 'registrations' | 'templates' | 'events' | 'jobs' | 'companies' | 'alumni' | 'talent' | 'tracer';
type CompanySection = 'dashboard' | 'talent' | 'jobs' | 'team' | 'inbox';
type SharedProps = { auth?: { actor?: { roles: string[]; capabilities: string[] } } };

export function useStaffNav(active: StaffSection): NavItem[] {
    const actor = usePage<SharedProps>().props.auth?.actor;
    const roles = actor?.roles || [];
    const capabilities = actor?.capabilities || [];
    const can = (capability: string) => capabilities.includes(capability);
    const operations: NavItem[] = [];

    if (can('registration.queue.view')) operations.push({ label: 'Registrasi Alumni', href: '/admin/registrations', active: active === 'registrations' });
    if (can('event.aggregate.view')) operations.push({ label: 'Event & Peserta', href: '/admin/events', active: active === 'events' });
    if (can('job.review')) operations.push({ label: 'Lowongan', href: '/admin/jobs', active: active === 'jobs' });
    if (can('company.manage')) operations.push({ label: 'Perusahaan', href: '/admin/companies', active: active === 'companies' });
    if (can('template.manage')) operations.push({ label: 'Template CV', href: '/admin/cv-templates', active: active === 'templates' });

    if (roles.includes('viewer-karir')) {
        return [
            { label: 'Ringkasan', href: '/staff', active: active === 'overview' },
            ...(can('alumni.directory.view') ? [{ label: 'Alumni', href: '/alumni', active: active === 'alumni' }] : []),
            ...(can('talent.directory.search.internal') ? [{ label: 'Pencarian Talenta', href: '/internal/talent', active: active === 'talent' }] : []),
            ...(can('event.aggregate.view') ? [{ label: 'Data Event', href: '/admin/events', active: active === 'events' }] : []),
        ];
    }

    return [
        { label: 'Ringkasan', href: '/staff', active: active === 'overview' },
        ...(operations.length ? [{ label: 'Kelola', active: operations.some(item => item.active), children: operations }] : []),
        ...(can('alumni.directory.view') ? [{ label: 'Alumni', href: '/alumni', active: active === 'alumni' }] : []),
        ...(can('talent.directory.search.internal') ? [{ label: 'Direktori Talenta', href: '/internal/talent', active: active === 'talent' }] : []),
        ...(can('tracer.manage') ? [{ label: 'Tracer', href: '/admin/tracer', active: active === 'tracer' }] : []),
    ];
}

export function companyNav(active: CompanySection, canSearch = true, canManageTeam = false): NavItem[] {
    return [
        { label: 'Dashboard', href: '/company/dashboard', active: active === 'dashboard' },
        { label: 'Cari Talenta', href: canSearch ? '/company/talent' : '#', active: active === 'talent', comingSoon: !canSearch },
        ...(canSearch ? [{ label: 'Rekrutmen', active: active === 'jobs', children: [
            { label: 'Lowongan Saya', href: '/company/jobs', active: active === 'jobs' },
            { label: 'Buat Lowongan', href: '/company/jobs/create' },
        ] }] : [{ label: 'Rekrutmen', comingSoon: true }]),
        ...(canManageTeam ? [{ label: 'Tim Rekruter', href: '/company/recruiters', active: active === 'team' }] : []),
        { label: 'Inbox', href: '/company/notifications', active: active === 'inbox' },
    ];
}
