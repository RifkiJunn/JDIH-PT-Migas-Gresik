import DocumentRow from '@/components/document-row';
import StatCards from '@/components/stat-cards';
import PublicLayout from '@/layouts/public-layout';
import { type Dokumen, type SharedData, type Statistik } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowRight, Search } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';

const PILIH =
    'text-foreground h-12 rounded-md bg-background px-3 text-base sm:text-sm lg:rounded-none lg:border-l lg:border-[var(--border)] lg:bg-transparent';

function Hero({ tahun }: { tahun: number[] }) {
    const { jenisPublik } = usePage<SharedData>().props;
    const [isi, setIsi] = useState({ q: '', jenis: '', tahun: '' });

    const cari: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('dokumen.index'), Object.fromEntries(Object.entries(isi).filter(([, v]) => v)));
    };

    return (
        <section className="bg-brand text-on-brand relative isolate overflow-hidden">
            {/* Nyala api dari logo, dipotong di tepi kanan; tulisan "ptgm" pada logo jatuh di luar bingkai. */}
            <div
                aria-hidden
                className="mask-flame bg-sky/25 absolute -top-[12%] right-[-6rem] -z-10 hidden aspect-[408/612] h-[175%] sm:block lg:right-[4%]"
            />

            <div className="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6 sm:py-20 lg:py-24">
                <h1 className="max-w-3xl font-serif text-[clamp(2.125rem,5.2vw,3.75rem)] leading-[1.08] font-semibold tracking-[-0.02em] text-balance">
                    Jaringan Dokumentasi dan Informasi Hukum
                </h1>
                <p className="text-on-primary-muted mt-5 max-w-xl text-lg leading-relaxed">
                    Peraturan perusahaan, keputusan direksi, dan dokumen PT Gresik Migas yang dibuka untuk umum.
                </p>

                <form
                    onSubmit={cari}
                    role="search"
                    className="lg:bg-background mt-9 grid max-w-4xl gap-2 lg:grid-cols-[1fr_auto_auto_auto] lg:gap-0 lg:rounded-lg lg:p-1.5 lg:shadow-[0_12px_32px_-12px_rgb(8_9_40/0.55)]"
                >
                    <label className="relative block">
                        <span className="sr-only">Cari judul atau nomor dokumen</span>
                        <Search aria-hidden className="text-muted-foreground pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2" />
                        <input
                            type="search"
                            value={isi.q}
                            onChange={(e) => setIsi({ ...isi, q: e.target.value })}
                            placeholder="Judul atau nomor dokumen"
                            className="text-foreground placeholder:text-muted-foreground bg-background h-12 w-full rounded-md pr-3 pl-12 text-base lg:bg-transparent"
                        />
                    </label>
                    <select
                        aria-label="Jenis dokumen"
                        value={isi.jenis}
                        onChange={(e) => setIsi({ ...isi, jenis: e.target.value })}
                        className={PILIH}
                    >
                        <option value="">Semua jenis</option>
                        {jenisPublik.map((j) => (
                            <option key={j.id} value={j.id}>
                                {j.nama}
                            </option>
                        ))}
                    </select>
                    <select
                        aria-label="Tahun penetapan"
                        value={isi.tahun}
                        onChange={(e) => setIsi({ ...isi, tahun: e.target.value })}
                        className={PILIH}
                    >
                        <option value="">Semua tahun</option>
                        {tahun.map((t) => (
                            <option key={t} value={t}>
                                {t}
                            </option>
                        ))}
                    </select>
                    <button
                        type="submit"
                        className="bg-sky text-sky-ink inline-flex h-12 items-center justify-center gap-2 rounded-md px-6 text-sm font-semibold transition-[filter,transform] duration-150 ease-out hover:brightness-110 active:scale-[0.97]"
                    >
                        Cari dokumen
                    </button>
                </form>

                {jenisPublik.length > 0 && (
                    <nav aria-label="Telusuri cepat" className="mt-5 flex flex-wrap items-center gap-2 text-sm">
                        <span className="text-on-primary-muted mr-1">Telusuri cepat:</span>
                        {jenisPublik.map((j) => (
                            <Link
                                key={j.id}
                                href={route('dokumen.index', { jenis: j.id })}
                                className="border-on-brand/20 text-on-brand hover:bg-on-brand/10 inline-flex min-h-9 items-center rounded-full border px-3.5 transition-colors duration-150"
                            >
                                {j.nama}
                            </Link>
                        ))}
                    </nav>
                )}
            </div>
        </section>
    );
}

interface Props {
    total: number;
    tahun: number[];
    statistik: Statistik[];
    terbaru: Dokumen[];
}

export default function Beranda({ total, tahun, statistik, terbaru }: Props) {
    const { jenisPublik } = usePage<SharedData>().props;

    return (
        <PublicLayout
            hero={
                <>
                    <Hero tahun={tahun} />
                    {total > 0 && (
                        // Kartu menumpang di batas bawah hero: separuh atas di bidang indigo, separuh bawah di halaman.
                        <section aria-label="Statistik koleksi" className="relative">
                            <div aria-hidden className="bg-brand absolute inset-x-0 top-0 h-1/2" />
                            <StatCards items={statistik} className="relative mx-auto w-full max-w-6xl px-4 sm:px-6" />
                        </section>
                    )}
                </>
            }
        >
            <Head title="Beranda" />

            {total === 0 ? (
                <div className="py-10">
                    <h2 className="font-serif text-2xl font-semibold">Belum ada dokumen yang dibuka untuk umum</h2>
                    <p className="text-muted-foreground mt-2 max-w-prose">
                        Dokumen tampil di sini setelah diajukan divisi pemiliknya dan disetujui General Manager.
                    </p>
                </div>
            ) : (
                <>
                    <section aria-labelledby="menurut-jenis" className="scroll-mt-24">
                        <h2 id="menurut-jenis" className="font-serif text-2xl font-semibold tracking-tight">
                            Telusuri menurut jenis
                        </h2>
                        <ul className="mt-5 grid gap-x-12 border-t sm:grid-cols-2">
                            {jenisPublik.map((j) => (
                                <li key={j.id} className="border-b">
                                    <Link
                                        href={route('dokumen.index', { jenis: j.id })}
                                        className="group flex min-h-16 items-center justify-between gap-4 py-3"
                                    >
                                        <span className="group-hover:text-primary font-serif text-lg font-medium transition-colors duration-150">
                                            {j.nama}
                                        </span>
                                        <span className="text-muted-foreground flex shrink-0 items-center gap-3 text-sm">
                                            {j.jumlah} dokumen
                                            <ArrowRight
                                                aria-hidden
                                                className="text-primary size-4 transition-transform duration-150 ease-out group-hover:translate-x-1"
                                            />
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section aria-labelledby="terbaru" className="mt-16">
                        <div className="flex flex-wrap items-baseline justify-between gap-4 border-b pb-4">
                            <h2 id="terbaru" className="font-serif text-2xl font-semibold tracking-tight">
                                Dokumen terbaru
                            </h2>
                            <Link
                                href={route('dokumen.index')}
                                className="text-primary inline-flex items-center gap-1.5 text-sm font-medium underline-offset-4 hover:underline"
                            >
                                Semua dokumen
                                <ArrowRight aria-hidden className="size-4" />
                            </Link>
                        </div>
                        <ul>
                            {terbaru.map((d) => (
                                <DocumentRow key={d.id} document={d} href={route('dokumen.show', d.id)} />
                            ))}
                        </ul>
                    </section>
                </>
            )}
        </PublicLayout>
    );
}
