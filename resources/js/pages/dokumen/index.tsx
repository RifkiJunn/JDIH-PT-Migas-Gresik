import DocumentCatalog from '@/components/document-catalog';
import { type Filter, type PilihanFilter } from '@/components/document-filter';
import PublicLayout from '@/layouts/public-layout';
import { type Dokumen, type Halaman } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

/** Kepala halaman katalog: pita indigo tipis yang menyambung hero beranda. */
function Kepala() {
    return (
        <section className="bg-brand text-on-brand relative isolate overflow-hidden">
            <div aria-hidden className="mask-flame bg-sky/20 absolute -top-[40%] right-[4%] -z-10 hidden aspect-[408/612] h-[260%] sm:block" />
            <div className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 sm:py-12">
                <nav aria-label="Remah roti" className="text-on-primary-muted flex items-center gap-1.5 text-sm">
                    <Link href={route('beranda')} className="hover:text-on-brand underline-offset-4 hover:underline">
                        Beranda
                    </Link>
                    <ChevronRight aria-hidden className="size-3.5" />
                    <span aria-current="page">Dokumen hukum</span>
                </nav>
                <h1 className="mt-3 font-serif text-3xl font-semibold tracking-tight text-balance sm:text-4xl">Katalog dokumen hukum</h1>
                <p className="text-on-primary-muted mt-2 max-w-2xl">
                    Peraturan perusahaan, keputusan direksi, dan dokumen lain PT Gresik Migas yang dibuka untuk umum.
                </p>
            </div>
        </section>
    );
}

export default function DokumenIndex({ dokumen, filter, pilihan }: { dokumen: Halaman<Dokumen>; filter: Filter; pilihan: PilihanFilter }) {
    return (
        <PublicLayout hero={<Kepala />}>
            <Head title="Dokumen hukum" />
            <DocumentCatalog
                action={route('dokumen.index')}
                dokumen={dokumen}
                filter={filter}
                pilihan={pilihan}
                kosong="Belum ada dokumen yang dibuka untuk umum."
            />
        </PublicLayout>
    );
}
