export type Peran = 'karyawan' | 'manajer' | 'gm' | 'admin';
export type Akses = 'publik' | 'divisi';
export type StatusDokumen = 'draf' | 'menunggu_manajer' | 'menunggu_gm' | 'disetujui' | 'ditolak';
export type JenisRelasi = 'mengubah' | 'mencabut' | 'menggantikan' | 'terkait';

export interface User {
    id: number;
    name: string;
    email: string;
    peran: Peran;
    division: { id: number; nama: string } | null;
}

export interface SharedData {
    name: string;
    auth: { user: User | null };
    flash: { sukses: string | null; galat: string | null };
    jenisPublik: { id: number; nama: string; jumlah: number }[];
    kontak: { pengelola?: string; alamat?: string; email?: string; telepon?: string };
    [key: string]: unknown;
}

export interface Pilihan {
    id: number;
    nama: string;
}

export interface Relasi {
    jenis: JenisRelasi;
    arah: 'keluar' | 'masuk';
    dokumen: { id: number; judul: string; nomor: string };
}

export interface Riwayat {
    id: number;
    aksi: 'ajukan' | 'setuju' | 'tolak';
    tahap: 'manajer' | 'gm';
    catatan: string | null;
    oleh: string;
    waktu: string;
}

export interface Dokumen {
    id: number;
    judul: string;
    nomor: string;
    jenis: string;
    divisi: string;
    akses: Akses;
    status: StatusDokumen;
    status_hukum: string;
    berakhir: boolean;
    tahun: number | null;
    tanggal_penetapan: string | null;
    tanggal_kadaluarsa: string | null;
    abstrak: string;
    subjek: string | null;
    pengaju?: string;
    berkas_nama?: string | null;
    berkas_ukuran?: number | null;
    boleh_unduh?: boolean;
    relasi?: Relasi[];
    riwayat?: Riwayat[];
}

export interface Halaman<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    from: number | null;
    to: number | null;
}
