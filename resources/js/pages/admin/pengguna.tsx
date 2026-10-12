import ConfirmDialog from '@/components/confirm-dialog';
import { DataTable, Td, Th } from '@/components/data-table';
import { Field, NativeSelect } from '@/components/field';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InternalLayout from '@/layouts/internal-layout';
import { type Peran, type Pilihan } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

interface Akun {
    id: number;
    name: string;
    email: string;
    peran: Peran;
    division_id: number | null;
    divisi: string | null;
}

const NAMA_PERAN: Record<Peran, string> = { karyawan: 'Karyawan', manajer: 'Manajer', gm: 'General Manager', admin: 'Admin' };
const KOSONG = { name: '', email: '', password: '', peran: 'karyawan' as Peran, division_id: '' as number | '' };

export default function Pengguna({ pengguna, divisi }: { pengguna: Akun[]; divisi: Pilihan[] }) {
    const [sunting, setSunting] = useState<Akun | 'baru' | null>(null);
    const form = useForm(KOSONG);
    const { data, setData, errors } = form;

    const perluDivisi = data.peran === 'karyawan' || data.peran === 'manajer';

    const buka = (akun: Akun | 'baru') => {
        form.clearErrors();
        form.setData(akun === 'baru' ? KOSONG : { name: akun.name, email: akun.email, password: '', peran: akun.peran, division_id: akun.division_id ?? '' });
        setSunting(akun);
    };

    const simpan = () => {
        const opsi = { preserveScroll: true, onSuccess: () => setSunting(null) };
        if (sunting === 'baru') form.post(route('admin.pengguna.store'), opsi);
        else if (sunting) form.put(route('admin.pengguna.update', sunting.id), opsi);
    };

    return (
        <InternalLayout>
            <Head title="Pengguna" />
            <PageHeader
                title="Pengguna"
                description="Peran menentukan menu dan wewenang; divisi menentukan dokumen yang terlihat."
                actions={
                    <Button onClick={() => buka('baru')}>
                        <Plus aria-hidden />
                        Tambah akun
                    </Button>
                }
            />

            <DataTable>
                <thead>
                    <tr>
                        <Th>Nama</Th>
                        <Th>Email</Th>
                        <Th>Peran</Th>
                        <Th>Divisi</Th>
                        <Th>
                            <span className="sr-only">Tindakan</span>
                        </Th>
                    </tr>
                </thead>
                <tbody>
                    {pengguna.map((u) => (
                        <tr key={u.id}>
                            <Td className="font-medium whitespace-nowrap">{u.name}</Td>
                            <Td className="text-muted-foreground">{u.email}</Td>
                            <Td>{NAMA_PERAN[u.peran]}</Td>
                            <Td>{u.divisi ?? <span className="text-muted-foreground">Semua divisi</span>}</Td>
                            <Td className="py-1.5 text-right">
                                <Button variant="ghost" size="sm" onClick={() => buka(u)}>
                                    Ubah
                                </Button>
                            </Td>
                        </tr>
                    ))}
                </tbody>
            </DataTable>

            <ConfirmDialog
                open={sunting !== null}
                onOpenChange={(b) => !b && setSunting(null)}
                title={sunting === 'baru' ? 'Tambah akun' : 'Ubah akun'}
                confirmLabel="Simpan"
                processing={form.processing}
                onConfirm={simpan}
            >
                <div className="grid gap-4">
                    <Field id="name" label="Nama" error={errors.name}>
                        <Input id="name" required value={data.name} onChange={(e) => setData('name', e.target.value)} />
                    </Field>
                    <Field id="email" label="Email" error={errors.email}>
                        <Input id="email" type="email" required value={data.email} onChange={(e) => setData('email', e.target.value)} />
                    </Field>
                    <Field
                        id="password"
                        label={sunting === 'baru' ? 'Password' : 'Password baru'}
                        optional={sunting !== 'baru'}
                        error={errors.password}
                        hint={sunting === 'baru' ? 'Minimal 8 karakter. Sampaikan langsung kepada pemilik akun.' : 'Isi hanya untuk mereset password akun ini.'}
                    >
                        <Input
                            id="password"
                            type="text"
                            autoComplete="off"
                            required={sunting === 'baru'}
                            minLength={8}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                        />
                    </Field>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field id="peran" label="Peran" error={errors.peran}>
                            <NativeSelect id="peran" value={data.peran} onChange={(e) => setData('peran', e.target.value as Peran)}>
                                {(Object.keys(NAMA_PERAN) as Peran[]).map((p) => (
                                    <option key={p} value={p}>
                                        {NAMA_PERAN[p]}
                                    </option>
                                ))}
                            </NativeSelect>
                        </Field>
                        <Field id="division_id" label="Divisi" error={errors.division_id} hint={perluDivisi ? undefined : 'Peran ini berlaku lintas divisi.'}>
                            <NativeSelect
                                id="division_id"
                                required={perluDivisi}
                                disabled={!perluDivisi}
                                value={perluDivisi ? data.division_id : ''}
                                onChange={(e) => setData('division_id', e.target.value ? Number(e.target.value) : '')}
                            >
                                <option value="">{perluDivisi ? 'Pilih divisi' : 'Semua divisi'}</option>
                                {divisi.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.nama}
                                    </option>
                                ))}
                            </NativeSelect>
                        </Field>
                    </div>
                </div>
            </ConfirmDialog>
        </InternalLayout>
    );
}
