import { NativeSelect } from '@/components/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { type Pilihan } from '@/types';
import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';

export interface Filter {
    q?: string;
    jenis?: string;
    tahun?: string;
    subjek?: string;
    kadaluarsa?: string;
}

export interface PilihanFilter {
    jenis: Pilihan[];
    tahun: number[];
    subjek: string[];
}

/** Pencarian + filter daftar dokumen. Keadaannya hidup di URL, jadi bisa dibagikan dan tombol kembali bekerja. */
export default function DocumentFilter({
    action,
    filter,
    pilihan,
    denganKadaluarsa,
}: {
    action: string;
    filter: Filter;
    pilihan: PilihanFilter;
    denganKadaluarsa?: boolean;
}) {
    const [q, setQ] = useState(filter.q ?? '');

    const terapkan = (ubah: Filter) => {
        const baru = Object.fromEntries(Object.entries({ ...filter, q, ...ubah }).filter(([, v]) => v));
        router.get(action, baru, { preserveState: true, preserveScroll: true, replace: true });
    };

    const cari: FormEventHandler = (e) => {
        e.preventDefault();
        terapkan({});
    };

    const adaFilter = Object.values(filter).some(Boolean);

    return (
        <form onSubmit={cari} role="search" className="grid gap-3">
            <div className="flex gap-2">
                <div className="relative flex-1">
                    <Search aria-hidden className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        type="search"
                        aria-label="Cari judul atau nomor dokumen"
                        placeholder="Cari judul atau nomor dokumen"
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        className="pl-9"
                    />
                </div>
                <Button type="submit">Cari</Button>
            </div>

            <div className="flex flex-wrap items-center gap-2">
                <NativeSelect aria-label="Jenis dokumen" value={filter.jenis ?? ''} onChange={(e) => terapkan({ jenis: e.target.value })} className="w-auto min-w-40 flex-1 sm:flex-none">
                    <option value="">Semua jenis</option>
                    {pilihan.jenis.map((j) => (
                        <option key={j.id} value={j.id}>
                            {j.nama}
                        </option>
                    ))}
                </NativeSelect>
                <NativeSelect aria-label="Tahun penetapan" value={filter.tahun ?? ''} onChange={(e) => terapkan({ tahun: e.target.value })} className="w-auto min-w-32 flex-1 sm:flex-none">
                    <option value="">Semua tahun</option>
                    {pilihan.tahun.map((t) => (
                        <option key={t} value={t}>
                            {t}
                        </option>
                    ))}
                </NativeSelect>
                {pilihan.subjek.length > 0 && (
                    <NativeSelect aria-label="Subjek" value={filter.subjek ?? ''} onChange={(e) => terapkan({ subjek: e.target.value })} className="w-auto min-w-40 flex-1 sm:flex-none">
                        <option value="">Semua subjek</option>
                        {pilihan.subjek.map((s) => (
                            <option key={s} value={s}>
                                {s}
                            </option>
                        ))}
                    </NativeSelect>
                )}
                {denganKadaluarsa && (
                    <label className="flex min-h-11 cursor-pointer items-center gap-2 px-1 text-sm sm:min-h-10">
                        <input
                            type="checkbox"
                            checked={Boolean(filter.kadaluarsa)}
                            onChange={(e) => terapkan({ kadaluarsa: e.target.checked ? '1' : '' })}
                            className="accent-primary size-4"
                        />
                        Kedaluwarsa dalam 90 hari
                    </label>
                )}
                {adaFilter && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            setQ('');
                            router.get(action, {}, { preserveScroll: true, replace: true });
                        }}
                    >
                        Hapus filter
                    </Button>
                )}
            </div>
        </form>
    );
}
