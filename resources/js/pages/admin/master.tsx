import ConfirmDialog from '@/components/confirm-dialog';
import { DataTable, Td, Th } from '@/components/data-table';
import { Field } from '@/components/field';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InternalLayout from '@/layouts/internal-layout';
import { Head, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

interface Butir {
    id: number;
    nama: string;
    perlu_kadaluarsa: boolean | null;
    dipakai: number;
}

const KET: Record<string, string> = {
    divisi: 'Dokumen dan akun dikelompokkan per divisi. Divisi yang masih punya akun atau dokumen tidak bisa dihapus.',
    'jenis-dokumen': 'Jenis yang wajib kadaluarsa meminta tanggal berakhir saat pengajuan, misalnya kontrak dan perizinan.',
};

export default function Master({ master, judul, butir }: { master: 'divisi' | 'jenis-dokumen'; judul: string; butir: Butir[] }) {
    const jenis = master === 'jenis-dokumen';
    const [sunting, setSunting] = useState<Butir | 'baru' | null>(null);
    const [hapus, setHapus] = useState<Butir | null>(null);
    const form = useForm<{ nama: string; perlu_kadaluarsa: boolean }>({ nama: '', perlu_kadaluarsa: false });

    const buka = (b: Butir | 'baru') => {
        form.clearErrors();
        form.setData(b === 'baru' ? { nama: '', perlu_kadaluarsa: false } : { nama: b.nama, perlu_kadaluarsa: Boolean(b.perlu_kadaluarsa) });
        setSunting(b);
    };

    const simpan = () => {
        const opsi = { preserveScroll: true, onSuccess: () => setSunting(null) };
        if (sunting === 'baru') form.post(route(`admin.${master}.store`), opsi);
        else if (sunting) form.put(route(`admin.${master}.update`, sunting.id), opsi);
    };

    return (
        <InternalLayout>
            <Head title={judul} />
            <PageHeader
                title={judul}
                description={KET[master]}
                actions={
                    <Button onClick={() => buka('baru')}>
                        <Plus aria-hidden />
                        Tambah
                    </Button>
                }
            />

            <DataTable>
                <thead>
                    <tr>
                        <Th>Nama</Th>
                        {jenis && <Th>Kadaluarsa</Th>}
                        <Th className="text-right">Dipakai</Th>
                        <Th>
                            <span className="sr-only">Tindakan</span>
                        </Th>
                    </tr>
                </thead>
                <tbody>
                    {butir.map((b) => (
                        <tr key={b.id}>
                            <Td className="font-medium">{b.nama}</Td>
                            {jenis && <Td>{b.perlu_kadaluarsa ? 'Wajib' : <span className="text-muted-foreground">Tidak wajib</span>}</Td>}
                            <Td className="text-right">{b.dipakai}</Td>
                            <Td className="py-1.5 text-right whitespace-nowrap">
                                <Button variant="ghost" size="sm" onClick={() => buka(b)}>
                                    Ubah
                                </Button>
                                <Button variant="ghost" size="sm" className="text-status-bad" disabled={b.dipakai > 0} onClick={() => setHapus(b)}>
                                    Hapus
                                </Button>
                            </Td>
                        </tr>
                    ))}
                </tbody>
            </DataTable>

            <ConfirmDialog
                open={sunting !== null}
                onOpenChange={(b) => !b && setSunting(null)}
                title={sunting === 'baru' ? `Tambah ${judul.toLowerCase()}` : `Ubah ${judul.toLowerCase()}`}
                confirmLabel="Simpan"
                processing={form.processing}
                onConfirm={simpan}
            >
                <div className="grid gap-4">
                    <Field id="nama" label="Nama" error={form.errors.nama}>
                        <Input id="nama" required autoFocus value={form.data.nama} onChange={(e) => form.setData('nama', e.target.value)} />
                    </Field>
                    {jenis && (
                        <label className="flex cursor-pointer items-center gap-3 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.perlu_kadaluarsa}
                                onChange={(e) => form.setData('perlu_kadaluarsa', e.target.checked)}
                                className="accent-primary size-4"
                            />
                            Wajib mengisi tanggal kadaluarsa
                        </label>
                    )}
                </div>
            </ConfirmDialog>

            <ConfirmDialog
                open={hapus !== null}
                onOpenChange={(b) => !b && setHapus(null)}
                title={`Hapus ${hapus?.nama ?? ''}?`}
                confirmLabel="Hapus"
                destructive
                onConfirm={() => hapus && router.delete(route(`admin.${master}.destroy`, hapus.id), { preserveScroll: true, onFinish: () => setHapus(null) })}
            />
        </InternalLayout>
    );
}
