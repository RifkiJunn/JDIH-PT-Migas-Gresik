import DocumentRow from '@/components/document-row';
import PageHeader from '@/components/page-header';
import StatCards from '@/components/stat-cards';
import InternalLayout, { NAMA_PERAN } from '@/layouts/internal-layout';
import { type Dokumen, type SharedData, type Statistik } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

export default function Ruang({ statistik, terbaru }: { statistik: Statistik[]; terbaru: Dokumen[] | null }) {
    const user = usePage<SharedData>().props.auth.user!;

    return (
        <InternalLayout>
            <Head title="Ringkasan" />
            <PageHeader title="Ringkasan" description={`${NAMA_PERAN[user.peran]} · ${user.division?.nama ?? 'Semua divisi'}`} />

            <StatCards items={statistik} />

            {terbaru && (
                <section aria-labelledby="terbaru" className="mt-12">
                    <div className="flex flex-wrap items-baseline justify-between gap-4 border-b pb-4">
                        <h2 id="terbaru" className="font-serif text-xl font-semibold tracking-tight">
                            Baru masuk arsip
                        </h2>
                        <Link href={route('arsip.index')} className="text-primary inline-flex items-center gap-1.5 text-sm font-medium underline-offset-4 hover:underline">
                            Buka arsip
                            <ArrowRight aria-hidden className="size-4" />
                        </Link>
                    </div>
                    {terbaru.length === 0 ? (
                        <p className="text-muted-foreground py-8 text-sm">Belum ada dokumen yang disetujui.</p>
                    ) : (
                        <ul>
                            {terbaru.map((d) => (
                                <DocumentRow key={d.id} document={d} href={route('dokumen.show', d.id)} />
                            ))}
                        </ul>
                    )}
                </section>
            )}
        </InternalLayout>
    );
}
