import type { ReactNode } from 'react';
import AppHeader from './AppHeader';
import { companyNav, useStaffNav } from './RoleNavigation';

type StaffSection = 'overview' | 'registrations' | 'templates' | 'events' | 'jobs' | 'companies' | 'alumni' | 'talent' | 'tracer';
type CompanySection = 'dashboard' | 'talent' | 'jobs' | 'team' | 'inbox';

export function StaffHeader({ context, active, actions, logout = true }: { context: string; active: StaffSection; actions?: ReactNode; logout?: boolean }) {
    return <AppHeader context={context} home="/staff" nav={useStaffNav(active)} actions={actions} logout={logout} />;
}

export function CompanyHeader({ context, active, canSearch = true, canManageTeam = false, actions }: { context: string; active: CompanySection; canSearch?: boolean; canManageTeam?: boolean; actions?: ReactNode }) {
    return <AppHeader context={context} home="/company/dashboard" nav={companyNav(active, canSearch, canManageTeam)} actions={actions} logout logoutUrl="/company/session" />;
}
