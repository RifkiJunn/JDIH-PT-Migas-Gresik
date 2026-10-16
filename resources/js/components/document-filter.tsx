import { NativeSelect } from '@/components/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { type Pilihan } from '@/types';
import { router } from '@inertiajs/react';
import { Search, SlidersHorizontal } from 'lucide-react';
import { type FormEventHandler, type ReactNode, useId, useState } from 'react';

export interface Filter {
    q?: string;
    nomor?: string;
    jenis?: string;
    tahun?: string;
    subjek?: string;
    divisi?: string;
    akses?: string;
    status?: string;
    kadaluarsa?: string;
    urut?: string;
}

export interface PilihanFilter {
    jenis: (Pilihan & { jumlah: number })[];
    tahun: number[];
    subjek: string[];
    divisi: Pilihan[];
}

export const STATUS_HUKUM = [
    { nilai: 'berlaku', label: 'Berlaku', warna: 'bg-status-ok' },
    { nilai: 'diubah', label: 'Diubah', warna: 'bg-status-wait' },
    { nilai: 'dicabut', label: 'Dicabut', warna: 'bg-status-bad' },
    { nilai: 'digantikan', label: 'Digantikan', warna: 'bg-status-off' },
    { nilai: 'berakhir', label: 'Berakhir', warna: 'bg-status-off' },
] as const;

/** Kunjungi ulang daftar dengan filter baru. Nilai kosong dibuang supaya URL tetap bersih. */
export function saring(action: string, filter: Filter) {
    router.get(action, Object.fromEntries(Object.entries(filter).filter(([, v]) => v)), { preserveState: true, preserveScroll: true, replace: true });
}

function Bagian({ judul, children }: { judul: string; children: ReactNode }) {
    return (
        <fieldset className="border-t px-5 py-4">
            <legend className="text-muted-foreground float-left mb-2.5 w-full text-xs font-semibold tracking-wide uppercase">{judul}</legend>
            <div className="clear-both">{children}</div>
        </fieldset>
    );
}

function Pilih({
    name,
    checked,
    onChange,
    children,
    jumlah,
}: {
    name: string;
    checked: boolean;
    onChange: () => void;
    children: ReactNode;
    jumlah?: number;
}) {
    return (
        <label
            className={cn(
                'hover:bg-surface -mx-2 flex min-h-9 cursor-pointer items-center gap-2.5 rounded-md px-2 text-sm transition-colors duration-150',
                checked && 'text-foreground font-medium',
            )}
        >
            <input type="radio" name={name} checked={checked} onChange={onChange} className="accent-primary size-4 shrink-0" />
            <span className="flex min-w-0 flex-1 items-center gap-2">{children}</span>
            {jumlah !== undefined && <span className="text-muted-foreground text-xs tabular-nums">{jumlah}</span>}
        </label>
    );
}

/**
 * Panel filter katalog dokumen. Keadaannya hidup di URL, jadi bisa dibagikan dan tombol kembali bekerja.
 * Pilihan (radio, select) langsung diterapkan; kolom teks diterapkan lewat tombol atau Enter.
 */
export default function DocumentFilter({
    action,
    filter,
    pilihan,
    internal,
}: {
    action: string;
    filter: Filter;
    pilihan: PilihanFilter;
    internal?: boolean;
}) {
    const id = useId();
    const [teks, setTeks] = useState({ q: filter.q ?? '', nomor: filter.nomor ?? '', tahun: filter.tahun ?? '' });
    const terapkan = (ubah: Filter) => saring(action, { ...filter, ...teks, ...ubah });
    const adaFilter = Object.entries(filter).some(([k, v]) => k !== 'urut' && v);

    const kirim: FormEventHandler = (e) => {
        e.preventDefault();
        terapkan({});
    };

    const kolom = [
        { k: 'q', label: 'Kata kunci', placeholder: 'Judul atau nomor', ikon: true },
        { k: 'nomor', label: 'Nomor dokumen', placeholder: 'mis. PP/004/2024' },
        { k: 'tahun', label: 'Tahun penetapan', placeholder: 'mis. 2024', angka: true },
    ] as const;

    return (
        <div className="bg-background rounded-lg border">
            <div className="flex items-center justify-between gap-2 px-5 py-4">
                <h2 className="flex items-center gap-2 font-serif text-lg font-semibold whitespace-nowrap">
                    <SlidersHorizontal aria-hidden className="text-primary size-4" />
                    Saring dokumen
                </h2>
                {adaFilter && (
                    <button
                        type="button"
                        onClick={() => {
                            setTeks({ q: '', nomor: '', tahun: '' });
                            saring(action, { urut: filter.urut });
                        }}
                        className="text-primary inline-flex min-h-9 items-center text-xs font-medium whitespace-nowrap underline-offset-4 hover:underline"
                    >
                        Atur ulang
                    </button>
                )}
            </div>

            <form onSubmit={kirim} role="search" className="grid gap-3 border-t px-5 py-4">
                {kolom.map((f) => (
                    <div key={f.k} className="grid gap-1.5">
                        <label htmlFor={`${id}-${f.k}`} className="text-sm font-medium">
                            {f.label}
                        </label>
                        <div className="relative">
                            {'ikon' in f && (
                                <Search
                                    aria-hidden
                                    className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                                />
                            )}
                            <Input
                                id={`${id}-${f.k}`}
                                type={f.k === 'q' ? 'search' : 'text'}
                                inputMode={'angka' in f ? 'numeric' : undefined}
                                maxLength={'angka' in f ? 4 : undefined}
                                placeholder={f.placeholder}
                                value={teks[f.k]}
                                onChange={(e) => setTeks({ ...teks, [f.k]: 'angka' in f ? e.target.value.replace(/\D/g, '') : e.target.value })}
                                className={cn('ikon' in f && 'pl-9')}
                            />
                        </div>
                    </div>
                ))}
                <Button type="submit" className="mt-1 w-full">
                    Terapkan
                </Button>
            </form>

            {pilihan.jenis.length > 0 && (
                <Bagian judul="Jenis dokumen">
                    <Pilih
                        name={`${id}-jenis`}
                        checked={!filter.jenis}
                        onChange={() => terapkan({ jenis: '' })}
                        jumlah={pilihan.jenis.reduce((n, j) => n + j.jumlah, 0)}
                    >
                        Semua jenis
                    </Pilih>
                    {pilihan.jenis.map((j) => (
                        <Pilih
                            key={j.id}
                            name={`${id}-jenis`}
                            checked={filter.jenis === String(j.id)}
                            onChange={() => terapkan({ jenis: String(j.id) })}
                            jumlah={j.jumlah}
                        >
                            <span className="truncate">{j.nama}</span>
                        </Pilih>
                    ))}
                </Bagian>
            )}

            <Bagian judul="Status hukum">
                <Pilih name={`${id}-status`} checked={!filter.status} onChange={() => terapkan({ status: '' })}>
                    Semua status
                </Pilih>
                {STATUS_HUKUM.map((s) => (
                    <Pilih key={s.nilai} name={`${id}-status`} checked={filter.status === s.nilai} onChange={() => terapkan({ status: s.nilai })}>
                        <span aria-hidden className={cn('size-2 shrink-0 rounded-full', s.warna)} />
                        {s.label}
                    </Pilih>
                ))}
            </Bagian>

            {internal && (
                <Bagian judul="Akses">
                    {[
                        ['', 'Semua akses'],
                        ['publik', 'Publik'],
                        ['divisi', 'Rahasia divisi'],
                    ].map(([nilai, label]) => (
                        <Pilih key={nilai} name={`${id}-akses`} checked={(filter.akses ?? '') === nilai} onChange={() => terapkan({ akses: nilai })}>
                            {label}
                        </Pilih>
                    ))}
                </Bagian>
            )}

            {(pilihan.subjek.length > 0 || (internal && pilihan.divisi.length > 1)) && (
                <Bagian judul="Lainnya">
                    <div className="grid gap-3">
                        {internal && pilihan.divisi.length > 1 && (
                            <NativeSelect
                                aria-label="Divisi pemilik"
                                value={filter.divisi ?? ''}
                                onChange={(e) => terapkan({ divisi: e.target.value })}
                            >
                                <option value="">Semua divisi</option>
                                {pilihan.divisi.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.nama}
                                    </option>
                                ))}
                            </NativeSelect>
                        )}
                        {pilihan.subjek.length > 0 && (
                            <NativeSelect aria-label="Subjek" value={filter.subjek ?? ''} onChange={(e) => terapkan({ subjek: e.target.value })}>
                                <option value="">Semua subjek</option>
                                {pilihan.subjek.map((s) => (
                                    <option key={s} value={s}>
                                        {s}
                                    </option>
                                ))}
                            </NativeSelect>
                        )}
                    </div>
                </Bagian>
            )}

            {internal && (
                <div className="border-t px-5 py-4">
                    <label className="flex min-h-9 cursor-pointer items-center gap-2.5 text-sm">
                        <input
                            type="checkbox"
                            checked={Boolean(filter.kadaluarsa)}
                            onChange={(e) => terapkan({ kadaluarsa: e.target.checked ? '1' : '' })}
                            className="accent-primary size-4"
                        />
                        Kedaluwarsa dalam 90 hari
                    </label>
                </div>
            )}
        </div>
    );
}
