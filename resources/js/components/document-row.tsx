import StatusBadge from '@/components/status-badge';
import { type Dokumen } from '@/types';
import { Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

const WARNA_HUKUM: Record<string, string> = {
    Berlaku: 'text-status-ok',
    Diubah: 'text-status-wait',
    Dicabut: 'text-status-bad',
    Digantikan: 'text-status-off',
};

export function StatusHukum({ nilai }: { nilai: string }) {
    return <span className={`text-xs font-medium ${WARNA_HUKUM[nilai] ?? 'text-status-off'}`}>{nilai}</span>;
}

/** Satu baris register dokumen. `aside` menggantikan kolom kanan bawaan (tahun + status hukum). */
export default function DocumentRow({ document, href, aside }: { document: Dokumen; href: string; aside?: ReactNode }) {
    return (
        <li className="grid gap-x-6 gap-y-1 border-b py-4 sm:grid-cols-[11rem_1fr_auto]">
            <div className="text-muted-foreground text-xs">
                <div className="text-foreground font-medium tracking-wide uppercase">{document.jenis}</div>
                <div className="mt-0.5 break-all">{document.nomor}</div>
            </div>
            <div className="min-w-0">
                <Link
                    href={href}
                    className="hover:text-primary font-serif text-lg leading-snug font-medium text-pretty underline-offset-4 hover:underline"
                >
                    {document.judul}
                </Link>
                <p className="text-muted-foreground mt-1 line-clamp-2 text-sm">{document.abstrak}</p>
            </div>
            <div className="flex items-baseline gap-3 text-sm sm:flex-col sm:items-end sm:gap-1">
                {aside ?? (
                    <>
                        <span className="text-muted-foreground">{document.tahun ?? '—'}</span>
                        {document.berakhir ? <StatusBadge status="berakhir" /> : <StatusHukum nilai={document.status_hukum} />}
                    </>
                )}
            </div>
        </li>
    );
}
