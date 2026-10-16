import { Button } from '@/components/ui/button';
import { tanggal, ukuran } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type Dokumen } from '@/types';
import { Link } from '@inertiajs/react';
import { Building2, CalendarDays, Download, Eye, FileText, Lock } from 'lucide-react';

const WARNA: Record<string, string> = {
    Berlaku: 'text-status-ok bg-status-ok/10',
    Diubah: 'text-status-wait bg-status-wait/10',
    Dicabut: 'text-status-bad bg-status-bad/10',
};

function sisaHari(iso: string): number {
    return Math.round((new Date(`${iso}T00:00:00`).getTime() - new Date().setHours(0, 0, 0, 0)) / 86_400_000);
}

/** Label status hukum dalam bentuk pil, termasuk "Berakhir" yang dihitung dari tanggal kedaluwarsa. */
export function StatusPil({ d }: { d: Dokumen }) {
    const label = d.berakhir ? 'Berakhir' : d.status_hukum;

    return (
        <span
            className={cn(
                'inline-flex h-6 items-center gap-1.5 rounded-full px-2.5 text-xs font-semibold whitespace-nowrap',
                WARNA[label] ?? 'text-status-off bg-status-off/10',
            )}
        >
            <span aria-hidden className="size-1.5 rounded-full bg-current" />
            {label}
        </span>
    );
}

/** Satu dokumen di katalog: identitas, ringkasan, keterangan, dan aksi Detail / Unduh PDF. */
export default function DocumentCard({ document: d, internal }: { document: Dokumen; internal?: boolean }) {
    const sisa = internal && d.tanggal_kadaluarsa && !d.berakhir ? sisaHari(d.tanggal_kadaluarsa) : null;
    const detail = route('dokumen.show', d.id);

    const meta = [
        { ikon: CalendarDays, teks: `Ditetapkan ${tanggal(d.tanggal_penetapan)}` },
        { ikon: Building2, teks: `Divisi ${d.divisi}` },
        d.dilihat != null && { ikon: Eye, teks: `${d.dilihat.toLocaleString('id-ID')} kali dilihat` },
        d.diunduh != null && { ikon: Download, teks: `${d.diunduh.toLocaleString('id-ID')} kali diunduh` },
        d.berkas_ukuran && { ikon: FileText, teks: `PDF, ${ukuran(d.berkas_ukuran)}` },
    ].filter(Boolean) as { ikon: typeof Eye; teks: string }[];

    return (
        <li className="bg-background hover:border-input rounded-lg border p-5 transition-colors duration-150 sm:p-6">
            <div className="flex flex-wrap items-center gap-x-3 gap-y-2 text-xs">
                <span className="bg-surface text-primary rounded-sm px-2 py-1 font-semibold tracking-wide uppercase">{d.jenis}</span>
                <span className="text-muted-foreground font-medium break-all">{d.nomor}</span>
                {internal && d.akses === 'divisi' && (
                    <span className="text-muted-foreground inline-flex items-center gap-1">
                        <Lock aria-hidden className="size-3" />
                        Rahasia divisi
                    </span>
                )}
                <span className="ml-auto">
                    <StatusPil d={d} />
                </span>
            </div>

            <h3 className="mt-3 font-serif text-lg leading-snug font-semibold text-pretty sm:text-xl">
                <Link href={detail} className="hover:text-primary underline-offset-4 hover:underline">
                    {d.judul}
                </Link>
            </h3>
            <p className="text-muted-foreground mt-2 line-clamp-2 max-w-[75ch] text-sm leading-relaxed">{d.abstrak}</p>

            <div className="mt-4 flex flex-wrap items-end justify-between gap-x-6 gap-y-4 border-t pt-4">
                <ul className="text-muted-foreground flex flex-wrap gap-x-5 gap-y-1.5 text-xs">
                    {meta.map(({ ikon: Ikon, teks }) => (
                        <li key={teks} className="inline-flex items-center gap-1.5">
                            <Ikon aria-hidden className="size-3.5 shrink-0" />
                            {teks}
                        </li>
                    ))}
                    {sisa !== null && sisa <= 90 && (
                        <li className="text-status-wait font-medium">{sisa === 0 ? 'Kedaluwarsa hari ini' : `Kedaluwarsa ${sisa} hari lagi`}</li>
                    )}
                </ul>
                <div className="flex shrink-0 gap-2">
                    <Button asChild variant="outline" size="sm">
                        <Link href={detail}>
                            <Eye aria-hidden />
                            Detail
                        </Link>
                    </Button>
                    <Button asChild size="sm">
                        <a href={route('berkas.unduh', d.id)}>
                            <Download aria-hidden />
                            Unduh PDF
                        </a>
                    </Button>
                </div>
            </div>
        </li>
    );
}
