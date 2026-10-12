import { Field, NativeSelect, Textarea } from '@/components/field';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InternalLayout from '@/layouts/internal-layout';
import { NAMA_RELASI } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type Akses, type Dokumen, type JenisRelasi, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle, Plus, Sparkles, X } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';

interface Props {
    dokumen: (Dokumen & { document_type_id: number; relasi: { jenis: JenisRelasi; document_id: number }[] }) | null;
    jenis: { id: number; nama: string; perlu_kadaluarsa: boolean }[];
    pilihanRelasi: { id: number; judul: string; nomor: string }[];
    ai: { aktif: boolean; izinkanDivisi: boolean };
}

type Isian = {
    akses: Akses;
    berkas: File | null;
    judul: string;
    nomor: string;
    document_type_id: number | '';
    tanggal_penetapan: string;
    tanggal_kadaluarsa: string;
    abstrak: string;
    subjek: string;
    relasi: { jenis: JenisRelasi; document_id: number | '' }[];
    ajukan: boolean;
};

type KolomAi = 'judul' | 'nomor' | 'document_type_id' | 'tanggal_penetapan' | 'tanggal_kadaluarsa' | 'abstrak';

const PILIHAN_AKSES: { nilai: Akses; judul: string; ket: (divisi: string) => string }[] = [
    { nilai: 'divisi', judul: 'Divisi', ket: (d) => `Hanya dapat dilihat oleh ${d} dan General Manager.` },
    { nilai: 'publik', judul: 'Publik', ket: () => 'Diusulkan tampil di JDIH untuk umum. Baru tayang setelah disetujui General Manager.' },
];

export default function PengajuanForm({ dokumen, jenis, pilihanRelasi, ai }: Props) {
    const { auth } = usePage<SharedData>().props;
    const divisi = auth.user?.division?.nama ?? 'divisi Anda';

    const form = useForm<Isian>({
        akses: dokumen?.akses ?? 'divisi',
        berkas: null,
        judul: dokumen?.judul ?? '',
        nomor: dokumen?.nomor ?? '',
        document_type_id: dokumen?.document_type_id ?? '',
        tanggal_penetapan: dokumen?.tanggal_penetapan ?? '',
        tanggal_kadaluarsa: dokumen?.tanggal_kadaluarsa ?? '',
        abstrak: dokumen?.abstrak ?? '',
        subjek: dokumen?.subjek ?? '',
        relasi: dokumen?.relasi ?? [],
        ajukan: false,
    });
    const { data, setData, errors, processing } = form;
    const galat = errors as Record<string, string | undefined>;

    const [periksa, setPeriksa] = useState<Set<KolomAi>>(new Set());
    const [aiStatus, setAiStatus] = useState<{ memuat: boolean; pesan: string | null; gagal: boolean }>({ memuat: false, pesan: null, gagal: false });

    const perluKadaluarsa = jenis.find((j) => j.id === data.document_type_id)?.perlu_kadaluarsa ?? false;
    const aiBoleh = data.akses === 'publik' || ai.izinkanDivisi;

    const ubah = <K extends keyof Isian>(kolom: K, nilai: Isian[K]) => {
        setData(kolom, nilai);
        if (periksa.has(kolom as KolomAi)) {
            setPeriksa((s) => new Set([...s].filter((k) => k !== kolom)));
        }
    };

    const kirim = (ajukan: boolean): FormEventHandler => (e) => {
        e.preventDefault();
        form.transform((d) => ({ ...d, ajukan, ...(dokumen ? { _method: 'put' } : {}) }));
        form.post(dokumen ? route('pengajuan.update', dokumen.id) : route('pengajuan.store'), { forceFormData: true });
    };

    const isiOtomatis = async () => {
        if (!data.berkas) return;
        setAiStatus({ memuat: true, pesan: null, gagal: false });

        const body = new FormData();
        body.append('berkas', data.berkas);
        body.append('akses', data.akses);
        const token = decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '');

        try {
            const res = await fetch('/pengajuan/ekstrak', {
                method: 'POST',
                body,
                headers: { Accept: 'application/json', 'X-XSRF-TOKEN': token },
                credentials: 'same-origin',
            });
            const json = await res.json().catch(() => ({}));

            if (!res.ok) {
                setAiStatus({ memuat: false, pesan: json.message ?? 'Isi otomatis gagal. Silakan isi manual.', gagal: true });
                return;
            }

            const terisi = (Object.keys(json) as KolomAi[]).filter((k) => json[k] !== null && json[k] !== '');
            setData((lama) => ({ ...lama, ...Object.fromEntries(terisi.map((k) => [k, json[k]])) }));
            setPeriksa(new Set(terisi));
            setAiStatus({
                memuat: false,
                gagal: terisi.length === 0,
                pesan: terisi.length ? `${terisi.length} kolom terisi otomatis. Periksa kembali sebelum menyimpan.` : 'Tidak ada yang bisa dibaca dari berkas ini. Silakan isi manual.',
            });
        } catch {
            setAiStatus({ memuat: false, pesan: 'Isi otomatis gagal. Silakan isi manual.', gagal: true });
        }
    };

    const tanda = (kolom: KolomAi) => (periksa.has(kolom) ? 'Diisi otomatis, periksa kembali.' : undefined);
    const kelasAi = (kolom: KolomAi) => (periksa.has(kolom) ? 'border-status-wait' : undefined);

    return (
        <InternalLayout>
            <Head title={dokumen ? 'Ubah pengajuan' : 'Ajukan dokumen'} />
            <PageHeader
                title={dokumen ? 'Ubah pengajuan' : 'Ajukan dokumen'}
                description={`Dokumen ini tercatat atas nama ${divisi}. Setelah diajukan, dokumen diperiksa manajer lalu General Manager.`}
            />

            <form onSubmit={kirim(false)} className="grid max-w-3xl gap-8">
                <fieldset className="grid gap-3">
                    <legend className="mb-3 text-sm font-medium">Siapa yang boleh melihat dokumen ini?</legend>
                    <div className="grid gap-3 sm:grid-cols-2">
                        {PILIHAN_AKSES.map((p) => (
                            <label
                                key={p.nilai}
                                className={cn(
                                    'flex cursor-pointer gap-3 rounded-md border p-4 transition-colors duration-150',
                                    data.akses === p.nilai ? 'border-primary bg-surface' : 'hover:bg-surface',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="akses"
                                    value={p.nilai}
                                    checked={data.akses === p.nilai}
                                    onChange={() => ubah('akses', p.nilai)}
                                    className="accent-primary mt-1 size-4"
                                />
                                <span>
                                    <span className="block text-sm font-semibold">{p.judul}</span>
                                    <span className="text-muted-foreground block text-sm">{p.ket(divisi)}</span>
                                </span>
                            </label>
                        ))}
                    </div>
                    <InputError message={errors.akses} />
                </fieldset>

                <section className="grid gap-3">
                    <Field
                        id="berkas"
                        label="Berkas dokumen (PDF)"
                        error={errors.berkas}
                        hint={dokumen ? `Berkas saat ini: ${dokumen.berkas_nama}. Pilih berkas baru hanya bila ingin menggantinya.` : 'Satu berkas PDF, maksimal 20 MB.'}
                    >
                        <input
                            id="berkas"
                            type="file"
                            accept="application/pdf,.pdf"
                            onChange={(e) => ubah('berkas', e.target.files?.[0] ?? null)}
                            className="border-input file:bg-surface file:text-foreground w-full rounded-md border text-sm file:mr-3 file:h-11 file:cursor-pointer file:border-0 file:border-r file:px-4 file:text-sm file:font-medium sm:file:h-10"
                        />
                    </Field>

                    {ai.aktif && (
                        <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <Button type="button" variant="outline" size="sm" disabled={!data.berkas || !aiBoleh || aiStatus.memuat} onClick={isiOtomatis}>
                                {aiStatus.memuat ? <LoaderCircle aria-hidden className="animate-spin" /> : <Sparkles aria-hidden />}
                                {aiStatus.memuat ? 'Membaca berkas…' : 'Isi otomatis'}
                            </Button>
                            <p role="status" className={cn('text-sm', aiStatus.gagal ? 'text-status-bad' : 'text-muted-foreground')}>
                                {!aiBoleh
                                    ? 'Isi otomatis hanya untuk dokumen berakses Publik, karena berkas dikirim ke layanan AI di luar perusahaan.'
                                    : (aiStatus.pesan ?? (data.berkas ? 'Membaca berkas dan mengisi kolom di bawah.' : 'Pilih berkas lebih dulu.'))}
                            </p>
                        </div>
                    )}
                </section>

                <section className="grid gap-5 border-t pt-8 sm:grid-cols-2">
                    <Field id="judul" label="Judul" error={errors.judul} hint={tanda('judul')} className="sm:col-span-2">
                        <Input id="judul" required value={data.judul} onChange={(e) => ubah('judul', e.target.value)} className={kelasAi('judul')} />
                    </Field>

                    <Field id="nomor" label="Nomor dokumen" error={errors.nomor} hint={tanda('nomor') ?? 'Contoh: SK-DIR/012/2026'}>
                        <Input id="nomor" required value={data.nomor} onChange={(e) => ubah('nomor', e.target.value)} className={kelasAi('nomor')} />
                    </Field>

                    <Field id="jenis" label="Jenis dokumen" error={errors.document_type_id} hint={tanda('document_type_id')}>
                        <NativeSelect
                            id="jenis"
                            required
                            value={data.document_type_id}
                            onChange={(e) => ubah('document_type_id', e.target.value ? Number(e.target.value) : '')}
                            className={kelasAi('document_type_id')}
                        >
                            <option value="">Pilih jenis</option>
                            {jenis.map((j) => (
                                <option key={j.id} value={j.id}>
                                    {j.nama}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>

                    <Field id="tanggal_penetapan" label="Tanggal penetapan" error={errors.tanggal_penetapan} hint={tanda('tanggal_penetapan')}>
                        <Input
                            id="tanggal_penetapan"
                            type="date"
                            required
                            value={data.tanggal_penetapan}
                            onChange={(e) => ubah('tanggal_penetapan', e.target.value)}
                            className={kelasAi('tanggal_penetapan')}
                        />
                    </Field>

                    <Field
                        id="tanggal_kadaluarsa"
                        label="Tanggal kadaluarsa"
                        optional={!perluKadaluarsa}
                        error={errors.tanggal_kadaluarsa}
                        hint={tanda('tanggal_kadaluarsa') ?? (perluKadaluarsa ? 'Wajib untuk jenis dokumen ini.' : 'Kosongkan bila berlaku sampai dicabut.')}
                    >
                        <Input
                            id="tanggal_kadaluarsa"
                            type="date"
                            required={perluKadaluarsa}
                            min={data.tanggal_penetapan || undefined}
                            value={data.tanggal_kadaluarsa}
                            onChange={(e) => ubah('tanggal_kadaluarsa', e.target.value)}
                            className={kelasAi('tanggal_kadaluarsa')}
                        />
                    </Field>

                    <Field id="abstrak" label="Abstrak" error={errors.abstrak} hint={tanda('abstrak') ?? 'Ringkasan isi dokumen dalam beberapa kalimat.'} className="sm:col-span-2">
                        <Textarea id="abstrak" required value={data.abstrak} onChange={(e) => ubah('abstrak', e.target.value)} className={kelasAi('abstrak')} />
                    </Field>

                    <Field id="subjek" label="Subjek" optional error={errors.subjek} hint="Topik dokumen, misalnya Tata Kelola atau K3LL.">
                        <Input id="subjek" value={data.subjek} onChange={(e) => ubah('subjek', e.target.value)} />
                    </Field>
                </section>

                <fieldset className="grid gap-3 border-t pt-8">
                    <legend className="float-left text-sm font-medium">Hubungan dengan dokumen lain</legend>
                    <p className="text-muted-foreground clear-left text-sm">
                        Isi bila dokumen ini mengubah, mencabut, atau menggantikan dokumen yang sudah ada, misalnya perpanjangan kontrak.
                    </p>

                    {data.relasi.map((r, i) => (
                        <div key={i} className="grid gap-2 sm:grid-cols-[11rem_1fr_auto]">
                            <NativeSelect
                                aria-label="Jenis hubungan"
                                value={r.jenis}
                                onChange={(e) => ubah('relasi', data.relasi.map((x, j) => (j === i ? { ...x, jenis: e.target.value as JenisRelasi } : x)))}
                            >
                                {(Object.keys(NAMA_RELASI) as JenisRelasi[]).map((k) => (
                                    <option key={k} value={k}>
                                        {NAMA_RELASI[k]}
                                    </option>
                                ))}
                            </NativeSelect>
                            <NativeSelect
                                aria-label="Dokumen"
                                required
                                value={r.document_id}
                                onChange={(e) => ubah('relasi', data.relasi.map((x, j) => (j === i ? { ...x, document_id: Number(e.target.value) } : x)))}
                            >
                                <option value="">Pilih dokumen</option>
                                {pilihanRelasi.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.nomor} · {p.judul}
                                    </option>
                                ))}
                            </NativeSelect>
                            <Button type="button" variant="outline" size="icon" aria-label="Hapus hubungan" onClick={() => ubah('relasi', data.relasi.filter((_, j) => j !== i))}>
                                <X aria-hidden />
                            </Button>
                            <InputError message={galat[`relasi.${i}.document_id`] ?? galat[`relasi.${i}.jenis`]} className="sm:col-span-3" />
                        </div>
                    ))}

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="justify-self-start"
                        disabled={pilihanRelasi.length === 0}
                        onClick={() => ubah('relasi', [...data.relasi, { jenis: 'mengubah', document_id: '' }])}
                    >
                        <Plus aria-hidden />
                        Tambah hubungan
                    </Button>
                </fieldset>

                <div className="flex flex-wrap gap-3 border-t pt-6">
                    <Button type="button" disabled={processing} onClick={kirim(true)}>
                        {processing && <LoaderCircle aria-hidden className="animate-spin" />}
                        Simpan dan ajukan
                    </Button>
                    <Button type="submit" variant="outline" disabled={processing}>
                        Simpan sebagai draf
                    </Button>
                    <Button asChild variant="ghost">
                        <Link href={route('pengajuan.index')}>Batal</Link>
                    </Button>
                </div>
            </form>
        </InternalLayout>
    );
}
