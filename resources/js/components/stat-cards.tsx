import { cn } from '@/lib/utils';
import { type Statistik } from '@/types';
import { Link } from '@inertiajs/react';
import {
    Archive,
    Building2,
    CalendarClock,
    CalendarDays,
    CircleCheck,
    ClipboardCheck,
    Download,
    Eye,
    FilePen,
    FileText,
    Hourglass,
    type LucideIcon,
    Tags,
    Undo2,
    Users,
} from 'lucide-react';

const IKON: Record<string, LucideIcon> = {
    arsip: Archive,
    antrean: ClipboardCheck,
    draf: FilePen,
    proses: Hourglass,
    ditolak: Undo2,
    segera: CalendarClock,
    disetujui: CircleCheck,
    pengguna: Users,
    divisi: Building2,
    jenis: Tags,
    akses: Eye,
    tahun: CalendarDays,
    unduh: Download,
};

/** Deret kartu statistik. Kartu ber-`href` menjadi tautan ke daftar yang dihitungnya. */
export default function StatCards({ items, className }: { items: Statistik[]; className?: string }) {
    return (
        <ul className={cn('grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4', className)}>
            {items.map(({ kunci, label, nilai, keterangan, href, sorot }) => {
                const Ikon = IKON[kunci] ?? FileText;
                const kartu = 'bg-background flex h-full flex-col rounded-lg border p-4 sm:p-5';
                // Jangkar di halaman yang sama cukup tautan biasa; selebihnya kunjungan Inertia.
                const Tautan = href?.startsWith('#') ? 'a' : Link;
                const isi = (
                    <>
                        <span className="flex items-start justify-between gap-3">
                            <span className="text-muted-foreground text-sm font-medium text-balance">{label}</span>
                            <span
                                aria-hidden
                                className={cn('grid size-9 shrink-0 place-items-center rounded-md', sorot ? 'bg-primary text-primary-foreground' : 'bg-surface text-primary')}
                            >
                                <Ikon className="size-[1.125rem]" />
                            </span>
                        </span>
                        <span className="mt-3 font-serif text-4xl leading-none font-semibold tracking-tight tabular-nums sm:text-[2.75rem]">
                            {nilai.toLocaleString('id-ID')}
                        </span>
                        <span className="text-muted-foreground mt-2 text-xs">{keterangan}</span>
                    </>
                );

                return (
                    <li key={kunci}>
                        {href ? (
                            <Tautan href={href} className={cn(kartu, 'hover:border-primary transition-[border-color,transform] duration-150 ease-out active:scale-[0.98]')}>
                                {isi}
                            </Tautan>
                        ) : (
                            <div className={kartu}>{isi}</div>
                        )}
                    </li>
                );
            })}
        </ul>
    );
}
