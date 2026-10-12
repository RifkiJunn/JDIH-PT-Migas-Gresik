const TANGGAL = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
const WAKTU = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

/** '2026-01-15' -> '15 Januari 2026'. Tanggal saja diurai sebagai waktu lokal agar tidak bergeser sehari. */
export function tanggal(iso: string | null | undefined): string {
    if (!iso) return '—';
    return TANGGAL.format(new Date(iso.length === 10 ? `${iso}T00:00:00` : iso));
}

export function waktu(iso: string): string {
    return WAKTU.format(new Date(iso));
}

export function ukuran(bytes: number | null | undefined): string {
    if (!bytes) return '';
    return bytes < 1024 * 1024 ? `${Math.max(1, Math.round(bytes / 1024))} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

export const NAMA_AKSES = { publik: 'Publik', divisi: 'Divisi' } as const;
export const NAMA_RELASI = { mengubah: 'Mengubah', mencabut: 'Mencabut', menggantikan: 'Menggantikan', terkait: 'Terkait dengan' } as const;
export const NAMA_RELASI_MASUK = { mengubah: 'Diubah oleh', mencabut: 'Dicabut oleh', menggantikan: 'Digantikan oleh', terkait: 'Terkait dengan' } as const;
