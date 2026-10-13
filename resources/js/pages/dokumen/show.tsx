import DocumentDetail from '@/components/document-detail';
import InternalLayout from '@/layouts/internal-layout';
import PublicLayout from '@/layouts/public-layout';
import { type Dokumen, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

export default function DokumenShow({ dokumen }: { dokumen: Dokumen }) {
    const { auth } = usePage<SharedData>().props;
    // Dokumen publik yang sudah berlaku selalu tampil dalam kerangka JDIH; selebihnya adalah halaman ruang internal.
    const publik = dokumen.akses === 'publik' && dokumen.status === 'disetujui';
    const Layout = auth.user && !publik ? InternalLayout : PublicLayout;
    const kembali = Layout === PublicLayout ? route('dokumen.index') : route('ruang');

    return (
        <Layout>
            <Head title={dokumen.judul} />
            <Link href={kembali} className="text-muted-foreground hover:text-foreground mb-6 inline-flex items-center gap-1.5 text-sm">
                <ArrowLeft aria-hidden className="size-4" />
                {Layout === PublicLayout ? 'Semua dokumen' : 'Kembali'}
            </Link>
            <DocumentDetail dokumen={dokumen} riwayat={Boolean(auth.user) && !publik} />
        </Layout>
    );
}
