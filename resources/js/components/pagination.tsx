import { type Halaman } from '@/types';
import { Link } from '@inertiajs/react';

export default function Pagination({ halaman, satuan = 'dokumen' }: { halaman: Halaman<unknown>; satuan?: string }) {
    const [sebelum, sesudah] = [halaman.links[0], halaman.links[halaman.links.length - 1]];

    if (!sebelum?.url && !sesudah?.url) return null;

    const tombol = (link: typeof sebelum, label: string) =>
        link.url ? (
            <Link href={link.url} preserveScroll className="hover:bg-surface inline-flex h-10 items-center rounded-md border px-4 text-sm">
                {label}
            </Link>
        ) : (
            <span aria-disabled className="text-muted-foreground inline-flex h-10 items-center rounded-md border px-4 text-sm opacity-50">
                {label}
            </span>
        );

    return (
        <nav aria-label="Halaman" className="mt-6 flex items-center justify-between gap-4">
            <p className="text-muted-foreground text-sm">
                {halaman.from}–{halaman.to} dari {halaman.total} {satuan}
            </p>
            <div className="flex gap-2">
                {tombol(sebelum, 'Sebelumnya')}
                {tombol(sesudah, 'Berikutnya')}
            </div>
        </nav>
    );
}
