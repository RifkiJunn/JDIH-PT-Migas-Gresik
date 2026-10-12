import { Link } from '@inertiajs/react';

export default function Brand({ sub = 'PT Gresik Migas' }: { sub?: string }) {
    return (
        <Link href={route('beranda')} className="flex items-center gap-3">
            <img src="/images/ptgm-logo.png" alt="" width={24} height={36} className="h-9 w-auto" />
            <span className="leading-tight">
                <span className="text-primary block font-serif text-lg font-semibold tracking-tight">JDIH</span>
                <span className="text-muted-foreground block text-xs">{sub}</span>
            </span>
        </Link>
    );
}
