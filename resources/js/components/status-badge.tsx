import { cn } from '@/lib/utils';
import { type StatusDokumen } from '@/types';

const TAMPILAN: Record<StatusDokumen | 'berakhir', [string, string]> = {
    draf: ['Draf', 'text-status-off'],
    menunggu_manajer: ['Menunggu Manajer', 'text-status-wait'],
    menunggu_gm: ['Menunggu GM', 'text-status-wait'],
    disetujui: ['Disetujui', 'text-status-ok'],
    ditolak: ['Ditolak', 'text-status-bad'],
    berakhir: ['Berakhir', 'text-status-off'],
};

export default function StatusBadge({ status, className }: { status: StatusDokumen | 'berakhir'; className?: string }) {
    const [label, warna] = TAMPILAN[status];

    return (
        <span className={cn('inline-flex items-center gap-1.5 text-xs font-medium whitespace-nowrap', warna, className)}>
            <span aria-hidden className="size-1.5 rounded-full bg-current" />
            {label}
        </span>
    );
}
