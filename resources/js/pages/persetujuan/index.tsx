import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import InternalLayout from '@/layouts/internal-layout';
import { waktu } from '@/lib/format';
import { type Dokumen } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function PersetujuanIndex({ dokumen }: { dokumen: (Dokumen & { diajukan: string })[] }) {
    return (
        <InternalLayout>
            <Head title="Persetujuan" />
            <PageHeader
                title="Menunggu keputusan Anda"
                description={dokumen.length > 0 ? `${dokumen.length} dokumen, yang paling lama menunggu di atas.` : undefined}
            />

            {dokumen.length === 0 ? (
                <div className="border-b pb-12">
                    <p className="font-serif text-lg">Tidak ada dokumen yang menunggu.</p>
                    <p className="text-muted-foreground mt-1 text-sm">Pengajuan baru akan muncul di halaman ini.</p>
                </div>
            ) : (
                <ul className="-mt-6">
                    {dokumen.map((d) => (
                        <li key={d.id} className="grid items-center gap-x-6 gap-y-3 border-b py-4 sm:grid-cols-[1fr_auto]">
                            <div className="min-w-0">
                                <div className="text-muted-foreground flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                    {d.akses === 'publik' && (
                                        <span className="bg-primary text-primary-foreground rounded-sm px-2 py-0.5 font-semibold">Diusulkan untuk publik</span>
                                    )}
                                    <span className="text-foreground font-medium tracking-wide uppercase">{d.jenis}</span>
                                    <span className="break-all">{d.nomor}</span>
                                </div>
                                <Link
                                    href={route('persetujuan.show', d.id)}
                                    className="hover:text-primary mt-1 block font-serif text-lg leading-snug font-medium text-pretty underline-offset-4 hover:underline"
                                >
                                    {d.judul}
                                </Link>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {d.pengaju} · {d.divisi} · diajukan {waktu(d.diajukan)}
                                </p>
                            </div>
                            <Button asChild variant="outline" className="justify-self-start">
                                <Link href={route('persetujuan.show', d.id)}>Tinjau</Link>
                            </Button>
                        </li>
                    ))}
                </ul>
            )}
        </InternalLayout>
    );
}
