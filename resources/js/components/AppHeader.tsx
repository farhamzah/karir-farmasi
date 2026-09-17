import { Link, router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Brand from './Brand';

export type NavItem = { label: string; href: string; active?: boolean; comingSoon?: boolean };

export default function AppHeader({ context, home = '/dashboard', nav = [], actions, logout = false }: {
    context: string;
    home?: string;
    nav?: NavItem[];
    actions?: ReactNode;
    logout?: boolean;
}) {
    return <header className="app-header">
        <Brand href={home} context={context} />
        {nav.length > 0 && <nav className="app-nav" aria-label="Navigasi aplikasi">{nav.map(item => item.comingSoon
            ? <span className="app-nav-soon" key={item.label}>{item.label}<small>Segera</small></span>
            : <Link className={item.active ? 'active' : ''} href={item.href} key={item.label}>{item.label}</Link>)}</nav>}
        <div className="app-header-actions">{actions}{logout && <button className="ghost-button" onClick={() => router.delete('/internal/session')}>Keluar</button>}</div>
    </header>;
}
