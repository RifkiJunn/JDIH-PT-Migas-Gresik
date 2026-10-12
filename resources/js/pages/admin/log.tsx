import { DataTable, Td, Th } from '@/components/data-table';
import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InternalLayout from '@/layouts/internal-layout';
import { waktu } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type Halaman } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

interface Baris {
    id: number;
    waktu: string;
    oleh: string;
    aksi: string;
    tahap: string | null;
    catatan: string | null;
    ip: string | null;
    dokumen: string;
    nomor: string | null;
}

type Tab = 'persetujuan' | 'akses';

const AKSI: Record<string, string> = { ajukan: 'Mengajukan', setuju: 'Menyetujui', tolak: 'Menolak', lihat: 'Melihat', unduh: 'Mengunduh' };
const TAHAP: Record<string, string> = { manajer: 'Manajer', gm: 'General Manager' };

export default function Log({ tab, log, filter }: { tab: Tab; log: Halaman<Baris>; filter: { dari: string | null; sampai: string | null } }) {
    const ke = (ubah: Record<string, string | null>) => {
        const query = Object.fromEntries(Object.entries({ tab, ...filter, ...ubah }).filter(([, v]) => v));
        router.get(route('admin.log'), query, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <InternalLayout>
            <Head title="Log" />
            <PageHeader title="Log" description="Catatan persetujuan dan akses dokumen. Catatan tidak bisa diubah atau dihapus." />

            <div className="flex flex-wrap items-end justify-between gap-4">
                <div role="tablist" aria-label="Jenis log" className="flex">
                    {(['persetujuan', 'akses'] as Tab[]).map((t) => (
                        <Link
                            key={t}
                            href={route('admin.log', { tab: t })}
                            role="tab"
                            aria-selected={tab === t}
                            className={cn(
                                'flex min-h-11 items-center border-b-2 px-4 text-sm capitalize sm:min-h-10',
                                tab === t ? 'border-sky text-primary font-semibold' : 'text-muted-foreground hover:text-foreground border-transparent',
                            )}
                        >
                            {t}
                        </Link>
                    ))}
                </div>
                <div className="flex gap-3">
                    {(['dari', 'sampai'] as const).map((k) => (
                        <div key={k} className="grid gap-1">
                            <Label htmlFor={k} className="text-xs capitalize">
                                {k}
                            </Label>
                            <Input id={k} type="date" value={filter[k] ?? ''} onChange={(e) => ke({ [k]: e.target.value || null })} className="w-40" />
                        </div>
                    ))}
                </div>
            </div>

            <div className="mt-4">
                {log.data.length === 0 ? (
                    <p className="text-muted-foreground border-y py-10 text-sm">Belum ada catatan pada rentang ini.</p>
                ) : (
                    <DataTable>
                        <thead>
                            <tr>
                                <Th>Waktu</Th>
                                <Th>Oleh</Th>
                                <Th>Tindakan</Th>
                                <Th>Dokumen</Th>
                                <Th>{tab === 'akses' ? 'Alamat IP' : 'Catatan'}</Th>
                            </tr>
                        </thead>
                        <tbody>
                            {log.data.map((l) => (
                                <tr key={l.id}>
                                    <Td className="text-muted-foreground whitespace-nowrap">{waktu(l.waktu)}</Td>
                                    <Td className="whitespace-nowrap">{l.oleh}</Td>
                                    <Td className={cn('whitespace-nowrap', l.aksi === 'tolak' && 'text-status-bad')}>
                                        {AKSI[l.aksi] ?? l.aksi}
                                        {l.tahap && l.aksi !== 'ajukan' && <span className="text-muted-foreground"> · {TAHAP[l.tahap]}</span>}
                                    </Td>
                                    <Td className="min-w-64">
                                        {l.dokumen}
                                        {l.nomor && <span className="text-muted-foreground block text-xs">{l.nomor}</span>}
                                    </Td>
                                    <Td className="text-muted-foreground max-w-xs">{(tab === 'akses' ? l.ip : l.catatan) ?? '—'}</Td>
                                </tr>
                            ))}
                        </tbody>
                    </DataTable>
                )}
            </div>

            <Pagination halaman={log} satuan="catatan" />
        </InternalLayout>
    );
}
