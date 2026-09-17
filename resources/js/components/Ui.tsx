import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

export function PageIntro({ eyebrow, title, description, actions }: { eyebrow: string; title: ReactNode; description?: string; actions?: ReactNode }) {
    return <div className="ds-page-intro"><div><p className="eyebrow">{eyebrow}</p><h1 className="page-title">{title}</h1>{description && <p className="page-lead">{description}</p>}</div>{actions && <div className="ds-page-actions">{actions}</div>}</div>;
}

export function ComingSoonCard({ icon, title, description }: { icon: string; title: string; description: string }) {
    return <article className="coming-soon-card"><span className="feature-icon" aria-hidden="true">{icon}</span><div><span className="soon-label">Segera hadir</span><h3>{title}</h3><p>{description}</p></div></article>;
}

export function EmptyState({ icon = '✦', title, description, action }: { icon?: string; title: string; description: string; action?: { label: string; href: string } }) {
    return <div className="ds-empty-state"><span aria-hidden="true">{icon}</span><h2>{title}</h2><p>{description}</p>{action && <Link className="primary-button" href={action.href}>{action.label}</Link>}</div>;
}

export function ReadOnlyBanner({ children }: { children: ReactNode }) {
    return <div className="read-only-banner"><span aria-hidden="true">◉</span><div><strong>Mode baca saja</strong><p>{children}</p></div></div>;
}
