import { Link } from '@inertiajs/react';

export default function Brand({ href = '/', context = 'FARMASI UBP' }: { href?: string; context?: string }) {
    return <Link className="brand" href={href} aria-label="SAFA KARIR">
        <img className="brand-logo" src="/images/logo-fakultas-farmasi-ubp.png" alt="Logo Fakultas Farmasi UBP" />
        <span><strong>SAFA KARIR</strong><small>{context}</small></span>
    </Link>;
}
