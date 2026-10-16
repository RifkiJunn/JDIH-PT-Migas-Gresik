import DocumentCatalog from '@/components/document-catalog';
import { type Filter, type PilihanFilter } from '@/components/document-filter';
import PageHeader from '@/components/page-header';
import InternalLayout from '@/layouts/internal-layout';
import { type Dokumen, type Halaman } from '@/types';
import { Head } from '@inertiajs/react';

export default function ArsipIndex({ dokumen, filter, pilihan }: { dokumen: Halaman<Dokumen>; filter: Filter; pilihan: PilihanFilter }) {
    return (
        <InternalLayout lebar>
            <Head title="Arsip" />
            <PageHeader title="Arsip" description="Dokumen yang sudah disetujui dan boleh Anda lihat: milik divisi Anda dan dokumen publik." />
            <DocumentCatalog
                action={route('arsip.index')}
                dokumen={dokumen}
                filter={filter}
                pilihan={pilihan}
                internal
                kosong="Arsip masih kosong. Dokumen muncul di sini setelah disetujui General Manager."
            />
        </InternalLayout>
    );
}
