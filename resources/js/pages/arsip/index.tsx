import DocumentFilter, { type Filter, type PilihanFilter } from '@/components/document-filter';
import DocumentRow, { StatusHukum } from '@/components/document-row';
import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import StatusBadge from '@/components/status-badge';
import InternalLayout from '@/layouts/internal-layout';
import { NAMA_AKSES } from '@/lib/format';
import { type Dokumen, type Halaman } from '@/types';
import { Head } from '@inertiajs/react';

function sisaHari(iso: string): number {
    return Math.round((new Date(`${iso}T00:00:00`).getTime() - new Date().setHours(0, 0, 0, 0)) / 86_400_000);
}

function Keadaan({ d }: { d: Dokumen }) {
    if (d.berakhir) return <StatusBadge status="berakhir" />;

    const sisa = d.tanggal_kadaluarsa ? sisaHari(d.tanggal_kadaluarsa) : null;
    if (sisa !== null && sisa <= 90) {
        return <span className="text-status-wait text-xs font-medium">{sisa === 0 ? 'Kedaluwarsa hari ini' : `Kedaluwarsa ${sisa} hari lagi`}</span>;
    }

    return <StatusHukum nilai={d.status_hukum} />;
}

export default function ArsipIndex({ dokumen, filter, pilihan }: { dokumen: Halaman<Dokumen>; filter: Filter; pilihan: PilihanFilter }) {
    const adaFilter = Object.values(filter).some(Boolean);

    return (
        <InternalLayout>
            <Head title="Arsip" />
            <PageHeader title="Arsip" description="Dokumen yang sudah disetujui dan boleh Anda lihat: milik divisi Anda dan dokumen publik." />

            <DocumentFilter action={route('arsip.index')} filter={filter} pilihan={pilihan} denganKadaluarsa />

            <p aria-live="polite" className="text-muted-foreground mt-6 border-b pb-3 text-sm">
                {dokumen.total} dokumen{adaFilter && ' sesuai pencarian'}
            </p>

            {dokumen.data.length > 0 ? (
                <ul>
                    {dokumen.data.map((d) => (
                        <DocumentRow
                            key={d.id}
                            document={d}
                            href={route('dokumen.show', d.id)}
                            aside={
                                <>
                                    <span className="text-muted-foreground text-xs">
                                        {d.divisi} · {NAMA_AKSES[d.akses]} · {d.tahun}
                                    </span>
                                    <Keadaan d={d} />
                                </>
                            }
                        />
                    ))}
                </ul>
            ) : (
                <div className="py-12">
                    <p className="font-serif text-lg">{adaFilter ? 'Tidak ada dokumen yang cocok.' : 'Arsip masih kosong.'}</p>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {adaFilter ? 'Coba kata kunci lain, atau hapus filter.' : 'Dokumen muncul di sini setelah disetujui General Manager.'}
                    </p>
                </div>
            )}

            <Pagination halaman={dokumen} />
        </InternalLayout>
    );
}
