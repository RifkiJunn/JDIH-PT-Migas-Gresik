import { StatusHukum } from '@/components/document-row';
import StatusBadge from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { NAMA_AKSES, NAMA_RELASI, NAMA_RELASI_MASUK, tanggal, ukuran, waktu } from '@/lib/format';
import { type Dokumen } from '@/types';
import { Link } from '@inertiajs/react';
import { Download, ExternalLink } from 'lucide-react';
import { type ReactNode } from 'react';

const AKSI = { ajukan: 'Diajukan', setuju: 'Disetujui', tolak: 'Ditolak' } as const;
const TAHAP = { manajer: 'tahap Manajer', gm: 'tahap General Manager' } as const;

function Butir({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="grid grid-cols-[9rem_1fr] gap-3 border-b py-2.5 text-sm">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="min-w-0 break-words">{children}</dd>
        </div>
    );
}

/** Isi halaman satu dokumen. Dipakai portal publik, arsip, dan layar persetujuan. */
export default function DocumentDetail({ dokumen, riwayat = false, children }: { dokumen: Dokumen; riwayat?: boolean; children?: ReactNode }) {
    const urlBerkas = route('berkas.lihat', dokumen.id);

    return (
        <article>
            <header className="border-b pb-6">
                <p className="text-muted-foreground text-xs">
                    <span className="text-foreground font-medium tracking-wide uppercase">{dokumen.jenis}</span>
                    <span className="mx-2">·</span>
                    <span className="break-all">{dokumen.nomor}</span>
                </p>
                <h1 className="mt-2 max-w-4xl font-serif text-2xl leading-tight font-semibold tracking-tight text-pretty sm:text-3xl">{dokumen.judul}</h1>
                <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1">
                    {dokumen.status === 'disetujui' ? (
                        dokumen.berakhir ? <StatusBadge status="berakhir" /> : <StatusHukum nilai={dokumen.status_hukum} />
                    ) : (
                        <StatusBadge status={dokumen.status} />
                    )}
                    {dokumen.akses === 'publik' && dokumen.status !== 'disetujui' && (
                        <span className="bg-primary text-primary-foreground rounded-sm px-2 py-0.5 text-xs font-semibold">Diusulkan untuk publik</span>
                    )}
                </div>
            </header>

            <div className="grid gap-x-10 gap-y-8 pt-6 lg:grid-cols-[1fr_20rem]">
                <div className="min-w-0">
                    {children}

                    <h2 className="text-sm font-semibold">Abstrak</h2>
                    <p className="mt-2 max-w-prose leading-relaxed whitespace-pre-line">{dokumen.abstrak}</p>

                    {dokumen.boleh_unduh ? (
                        <>
                            <h2 className="mt-8 text-sm font-semibold">Naskah</h2>
                            <iframe src={urlBerkas} title={`Naskah ${dokumen.nomor}`} className="mt-2 hidden h-[75vh] w-full rounded-md border md:block" />
                            <div className="mt-3 flex flex-wrap items-center gap-3">
                                <Button asChild>
                                    <a href={route('berkas.unduh', dokumen.id)}>
                                        <Download aria-hidden />
                                        Unduh PDF
                                    </a>
                                </Button>
                                <Button asChild variant="outline">
                                    <a href={urlBerkas} target="_blank" rel="noreferrer">
                                        <ExternalLink aria-hidden />
                                        Buka di tab baru
                                    </a>
                                </Button>
                                <span className="text-muted-foreground text-sm">
                                    {dokumen.berkas_nama} · {ukuran(dokumen.berkas_ukuran)}
                                </span>
                            </div>
                        </>
                    ) : (
                        <p className="text-muted-foreground mt-8 border-l-2 pl-3 text-sm">Naskah dokumen ini hanya dapat dibuka oleh divisi pemiliknya.</p>
                    )}
                </div>

                <aside className="min-w-0">
                    <h2 className="text-sm font-semibold">Keterangan</h2>
                    <dl className="mt-2 border-t">
                        <Butir label="Nomor">{dokumen.nomor}</Butir>
                        <Butir label="Jenis">{dokumen.jenis}</Butir>
                        <Butir label="Ditetapkan">{tanggal(dokumen.tanggal_penetapan)}</Butir>
                        {dokumen.tanggal_kadaluarsa && <Butir label="Berlaku sampai">{tanggal(dokumen.tanggal_kadaluarsa)}</Butir>}
                        {dokumen.subjek && <Butir label="Subjek">{dokumen.subjek}</Butir>}
                        <Butir label="Divisi pemilik">{dokumen.divisi}</Butir>
                        <Butir label="Akses">{NAMA_AKSES[dokumen.akses]}</Butir>
                    </dl>

                    {dokumen.relasi && dokumen.relasi.length > 0 && (
                        <>
                            <h2 className="mt-8 text-sm font-semibold">Dokumen terkait</h2>
                            <ul className="mt-2 border-t">
                                {dokumen.relasi.map((r) => (
                                    <li key={`${r.arah}-${r.dokumen.id}`} className="border-b py-2.5 text-sm">
                                        <span className="text-muted-foreground block text-xs">
                                            {(r.arah === 'keluar' ? NAMA_RELASI : NAMA_RELASI_MASUK)[r.jenis]} · {r.dokumen.nomor}
                                        </span>
                                        <Link href={route('dokumen.show', r.dokumen.id)} className="text-primary underline underline-offset-4">
                                            {r.dokumen.judul}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}

                    {riwayat && dokumen.riwayat && dokumen.riwayat.length > 0 && (
                        <>
                            <h2 className="mt-8 text-sm font-semibold">Riwayat persetujuan</h2>
                            <ol className="mt-2 border-t">
                                {dokumen.riwayat.map((l) => (
                                    <li key={l.id} className="border-b py-2.5 text-sm">
                                        <span className={l.aksi === 'tolak' ? 'text-status-bad font-medium' : 'font-medium'}>
                                            {AKSI[l.aksi]}
                                            {l.aksi !== 'ajukan' && ` di ${TAHAP[l.tahap]}`}
                                        </span>
                                        <span className="text-muted-foreground block text-xs">
                                            {l.oleh} · {waktu(l.waktu)}
                                        </span>
                                        {l.catatan && <p className="mt-1">{l.catatan}</p>}
                                    </li>
                                ))}
                            </ol>
                        </>
                    )}
                </aside>
            </div>
        </article>
    );
}
