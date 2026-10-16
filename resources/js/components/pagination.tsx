import { cn } from '@/lib/utils';
import { type Halaman } from '@/types';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

/** Navigasi halaman: ringkasan "x–y dari n", lalu Sebelumnya, nomor halaman, Berikutnya. */
export default function Pagination({ halaman, satuan = 'dokumen' }: { halaman: Halaman<unknown>; satuan?: string }) {
    const links = halaman.links;
    const [sebelum, sesudah] = [links[0], links[links.length - 1]];

    if (!sebelum?.url && !sesudah?.url) return null;

    const kotak = 'inline-flex h-10 min-w-10 items-center justify-center gap-1 rounded-md border px-3 text-sm';

    const tombol = (link: (typeof links)[number], isi: React.ReactNode, label?: string) =>
        link.url && !link.active ? (
            <Link
                href={link.url}
                preserveScroll
                aria-label={label}
                className={cn(kotak, 'hover:bg-surface hover:border-input transition-colors duration-150')}
            >
                {isi}
            </Link>
        ) : (
            <span
                aria-disabled={!link.active}
                aria-current={link.active ? 'page' : undefined}
                className={cn(
                    kotak,
                    link.active ? 'bg-primary text-primary-foreground border-primary font-semibold' : 'text-muted-foreground opacity-50',
                )}
            >
                {isi}
            </span>
        );

    return (
        <nav aria-label="Halaman" className="mt-8 flex flex-wrap items-center justify-between gap-4">
            <p className="text-muted-foreground text-sm">
                Menampilkan {halaman.from}–{halaman.to} dari {halaman.total.toLocaleString('id-ID')} {satuan}
            </p>
            <div className="flex flex-wrap gap-1.5">
                {tombol(
                    sebelum,
                    <>
                        <ChevronLeft aria-hidden className="size-4" />
                        <span className="hidden sm:inline">Sebelumnya</span>
                    </>,
                    'Halaman sebelumnya',
                )}
                {links.slice(1, -1).map((l, i) => (
                    <span key={i} className="hidden sm:contents">
                        {l.url ? (
                            tombol(l, l.label, `Halaman ${l.label}`)
                        ) : (
                            <span className="text-muted-foreground inline-flex h-10 items-center px-1">…</span>
                        )}
                    </span>
                ))}
                {tombol(
                    sesudah,
                    <>
                        <span className="hidden sm:inline">Berikutnya</span>
                        <ChevronRight aria-hidden className="size-4" />
                    </>,
                    'Halaman berikutnya',
                )}
            </div>
        </nav>
    );
}
