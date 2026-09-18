import { Link, router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import Brand from './Brand';

export type NavItem = { label: string; href?: string; active?: boolean; comingSoon?: boolean; children?: NavItem[] };

function NavigationItems({ items, closeMenu }: { items: NavItem[]; closeMenu: () => void }) {
    return <>{items.map(item => item.children
        ? <details className={`app-nav-group${item.active ? ' active' : ''}`} key={item.label}>
            <summary>{item.label}<span aria-hidden="true">⌄</span></summary>
            <div className="app-nav-popover">{item.children.map(child => <Link className={child.active ? 'active' : ''} href={child.href || '#'} key={child.label} onClick={closeMenu}>{child.label}</Link>)}</div>
        </details>
        : item.comingSoon
            ? <span className="app-nav-soon" key={item.label}>{item.label}<small>Segera</small></span>
            : <Link className={item.active ? 'active' : ''} href={item.href || '#'} key={item.label} onClick={closeMenu}>{item.label}</Link>)}</>;
}

export default function AppHeader({ context, home = '/dashboard', nav = [], actions, logout = false, logoutUrl = '/internal/session' }: {
    context: string;
    home?: string;
    nav?: NavItem[];
    actions?: ReactNode;
    logout?: boolean;
    logoutUrl?: string;
}) {
    const [menuOpen, setMenuOpen] = useState(false);
    const closeMenu = () => setMenuOpen(false);
    const roleContext = usePage<{ auth?: { role_context?: { can_switch?: boolean } | null } }>().props.auth?.role_context;

    return <header className="app-header">
        <Brand href={home} context={context} />
        {nav.length > 0 && <nav className={`app-nav${menuOpen ? ' is-open' : ''}`} aria-label="Navigasi aplikasi"><NavigationItems items={nav} closeMenu={closeMenu} /></nav>}
        <div className="app-header-actions"><span className="app-trust-mark"><i aria-hidden="true" />Akses terverifikasi</span>{roleContext?.can_switch && <Link className="app-role-switch" href="/pilih-role">Ganti ruang</Link>}{actions}{logout && <button className="ghost-button" onClick={() => router.delete(logoutUrl)}>Keluar</button>}</div>
        {nav.length > 0 && <button className="app-menu-toggle" type="button" aria-expanded={menuOpen} aria-label={menuOpen ? 'Tutup menu' : 'Buka menu'} onClick={() => setMenuOpen(value => !value)}><span>{menuOpen ? '×' : '☰'}</span><small>Menu</small></button>}
    </header>;
}
