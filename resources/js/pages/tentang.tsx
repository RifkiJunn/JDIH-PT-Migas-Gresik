import PublicLayout from '@/layouts/public-layout';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Tentang() {
    const { kontak, jenisPublik } = usePage<SharedData>().props;
    const adaKontak = Object.keys(kontak).length > 0;

    return (
        <PublicLayout>
            <Head title="Tentang JDIH" />

            <article className="max-w-[68ch]">
                <h1 className="font-serif text-3xl font-semibold tracking-tight text-balance sm:text-4xl">Tentang JDIH PT Gresik Migas</h1>
                <p className="mt-5 text-lg leading-relaxed">
                    JDIH adalah singkatan dari Jaringan Dokumentasi dan Informasi Hukum. Portal ini menjadi satu tempat untuk menemukan dokumen PT Gresik Migas
                    yang dibuka untuk umum, lengkap dengan naskah PDF yang dapat dibaca dan diunduh tanpa perlu masuk.
                </p>

                <h2 className="mt-12 font-serif text-xl font-semibold">Apa yang dimuat</h2>
                <p className="mt-3 leading-relaxed">
                    Hanya dokumen yang oleh divisi pemiliknya diusulkan untuk publik dan sudah disetujui. Dokumen internal perusahaan tidak tampil di portal
                    ini.
                </p>
                {jenisPublik.length > 0 && (
                    <ul className="mt-4 border-t">
                        {jenisPublik.map((j) => (
                            <li key={j.id} className="flex justify-between gap-4 border-b py-2.5">
                                <Link href={route('dokumen.index', { jenis: j.id })} className="text-primary underline underline-offset-4">
                                    {j.nama}
                                </Link>
                                <span className="text-muted-foreground text-sm">{j.jumlah} dokumen</span>
                            </li>
                        ))}
                    </ul>
                )}

                <h2 className="mt-12 font-serif text-xl font-semibold">Bagaimana dokumen sampai ke sini</h2>
                <ol className="mt-3 list-decimal space-y-2 pl-5 leading-relaxed">
                    <li>Karyawan dari divisi pemilik mengunggah naskah dan melengkapi keterangannya.</li>
                    <li>Manajer divisi memeriksa dan menyetujui pengajuan itu.</li>
                    <li>General Manager memberi persetujuan akhir. Baru setelah itu dokumen tampil di portal.</li>
                </ol>
                <p className="mt-3 leading-relaxed">
                    Dokumen yang sudah tampil tidak diubah. Bila sebuah dokumen diubah, dicabut, atau digantikan, dokumen penggantinya dimuat tersendiri dan
                    keduanya saling ditautkan, sehingga status setiap dokumen selalu terlihat.
                </p>

                <h2 className="mt-12 font-serif text-xl font-semibold">Cara mencari</h2>
                <p className="mt-3 leading-relaxed">
                    Ketik sebagian judul atau nomor dokumen di kolom pencarian, lalu persempit dengan jenis, tahun penetapan, atau subjek.{' '}
                    <Link href={route('dokumen.index')} className="text-primary underline underline-offset-4">
                        Lihat semua dokumen
                    </Link>
                    .
                </p>

                {adaKontak && (
                    <>
                        <h2 className="mt-12 font-serif text-xl font-semibold">Pengelola</h2>
                        <address className="mt-3 leading-relaxed not-italic">
                            {kontak.pengelola && <span className="block font-medium">{kontak.pengelola}</span>}
                            {kontak.alamat && <span className="block">{kontak.alamat}</span>}
                            {kontak.email && (
                                <a href={`mailto:${kontak.email}`} className="text-primary block underline underline-offset-4">
                                    {kontak.email}
                                </a>
                            )}
                            {kontak.telepon && <span className="block">{kontak.telepon}</span>}
                        </address>
                    </>
                )}
            </article>
        </PublicLayout>
    );
}
