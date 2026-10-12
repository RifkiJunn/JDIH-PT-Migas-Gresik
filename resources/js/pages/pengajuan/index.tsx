import ConfirmDialog from '@/components/confirm-dialog';
import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import InternalLayout from '@/layouts/internal-layout';
import { NAMA_AKSES, tanggal } from '@/lib/format';
import { type Dokumen, type Halaman } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

type Baris = Dokumen & { catatan_penolakan: string | null };

export default function PengajuanIndex({ dokumen }: { dokumen: Halaman<Baris> }) {
    const [hapus, setHapus] = useState<Baris | null>(null);
    const [proses, setProses] = useState(false);

    const bisaDiubah = (d: Baris) => d.status === 'draf' || d.status === 'ditolak';

    return (
        <InternalLayout>
            <Head title="Pengajuan" />
            <PageHeader
                title="Pengajuan saya"
                description="Dokumen yang Anda ajukan dan tahap persetujuannya."
                actions={
                    <Button asChild>
                        <Link href={route('pengajuan.create')}>
                            <Plus aria-hidden />
                            Ajukan dokumen
                        </Link>
                    </Button>
                }
            />

            {dokumen.data.length === 0 ? (
                <div className="border-y py-12">
                    <p className="font-serif text-lg">Belum ada pengajuan.</p>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Unggah PDF dokumen, lengkapi keterangannya, lalu ajukan. Manajer divisi Anda akan menerimanya lebih dulu.
                    </p>
                </div>
            ) : (
                <ul className="border-t">
                    {dokumen.data.map((d) => (
                        <li key={d.id} className="grid gap-x-6 gap-y-2 border-b py-4 sm:grid-cols-[1fr_auto]">
                            <div className="min-w-0">
                                <div className="text-muted-foreground flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                    <StatusBadge status={d.status} />
                                    <span>{d.jenis}</span>
                                    <span className="break-all">{d.nomor}</span>
                                    <span>Akses {NAMA_AKSES[d.akses]}</span>
                                    <span>Ditetapkan {tanggal(d.tanggal_penetapan)}</span>
                                </div>
                                <Link
                                    href={bisaDiubah(d) ? route('pengajuan.edit', d.id) : route('dokumen.show', d.id)}
                                    className="hover:text-primary mt-1 block font-serif text-lg leading-snug font-medium text-pretty underline-offset-4 hover:underline"
                                >
                                    {d.judul}
                                </Link>
                                {d.catatan_penolakan && (
                                    <p className="border-status-bad mt-2 border-l-2 pl-3 text-sm">
                                        <span className="text-status-bad font-medium">Catatan penolakan: </span>
                                        {d.catatan_penolakan}
                                    </p>
                                )}
                            </div>
                            {bisaDiubah(d) && (
                                <div className="flex items-start gap-2">
                                    <Button
                                        size="sm"
                                        onClick={() => router.post(route('pengajuan.ajukan', d.id), {}, { preserveScroll: true })}
                                    >
                                        Ajukan
                                    </Button>
                                    <Button asChild size="sm" variant="outline">
                                        <Link href={route('pengajuan.edit', d.id)}>Ubah</Link>
                                    </Button>
                                    <Button size="sm" variant="ghost" className="text-status-bad" onClick={() => setHapus(d)}>
                                        Hapus
                                    </Button>
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            <Pagination halaman={dokumen} satuan="pengajuan" />

            <ConfirmDialog
                open={hapus !== null}
                onOpenChange={(buka) => !buka && setHapus(null)}
                title="Hapus pengajuan ini?"
                description={hapus && `"${hapus.judul}" akan dihapus dari daftar pengajuan Anda.`}
                confirmLabel="Hapus pengajuan"
                destructive
                processing={proses}
                onConfirm={() =>
                    hapus &&
                    router.delete(route('pengajuan.destroy', hapus.id), {
                        preserveScroll: true,
                        onStart: () => setProses(true),
                        onFinish: () => {
                            setProses(false);
                            setHapus(null);
                        },
                    })
                }
            />
        </InternalLayout>
    );
}
