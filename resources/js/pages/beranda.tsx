import DocumentFilter, { type Filter, type PilihanFilter } from '@/components/document-filter';
import DocumentRow from '@/components/document-row';
import Pagination from '@/components/pagination';
import PublicLayout from '@/layouts/public-layout';
import { type Dokumen, type Halaman } from '@/types';
import { Head } from '@inertiajs/react';

export default function Beranda({ dokumen, filter, pilihan }: { dokumen: Halaman<Dokumen>; filter: Filter; pilihan: PilihanFilter }) {
    const adaFilter = Object.values(filter).some(Boolean);

    return (
        <PublicLayout>
            <Head title="Dokumen hukum" />

            <h1 className="font-serif text-3xl font-semibold tracking-tight text-balance sm:text-4xl">Dokumen hukum PT Gresik Migas</h1>
            <p className="text-muted-foreground mt-2 max-w-2xl">
                Peraturan perusahaan, keputusan direksi, dan dokumen lain yang dibuka untuk umum. Cari berdasarkan judul atau nomor.
            </p>

            <div className="mt-8">
                <DocumentFilter action={route('beranda')} filter={filter} pilihan={pilihan} />
            </div>

            <p aria-live="polite" className="text-muted-foreground mt-8 border-b pb-3 text-sm">
                {dokumen.total} dokumen{adaFilter && ' sesuai pencarian'}
            </p>

            {dokumen.data.length > 0 ? (
                <ul>
                    {dokumen.data.map((d) => (
                        <DocumentRow key={d.id} document={d} href={route('dokumen.show', d.id)} />
                    ))}
                </ul>
            ) : (
                <div className="py-12">
                    <p className="font-serif text-lg">{adaFilter ? 'Tidak ada dokumen yang cocok.' : 'Belum ada dokumen yang dibuka untuk umum.'}</p>
                    {adaFilter && <p className="text-muted-foreground mt-1 text-sm">Coba kata kunci lain, atau hapus filter jenis dan tahun.</p>}
                </div>
            )}

            <Pagination halaman={dokumen} />
        </PublicLayout>
    );
}
