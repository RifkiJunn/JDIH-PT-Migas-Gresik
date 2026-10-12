# JDIH PT Gresik Migas

Satu portal dengan dua ruang: portal publik dokumen hukum (JDIH) dan arsip dokumen
internal per divisi dengan persetujuan dua tahap (Manajer, lalu General Manager).

- Rancangan: `docs/superpowers/specs/2026-10-13-jdih-ptgm-design.md`
- Rencana implementasi: `docs/superpowers/plans/2026-10-13-jdih-ptgm.md`

Laravel 12, Inertia + React + TypeScript, Tailwind 4, PostgreSQL, Pest.

## Memasang

Butuh PHP 8.2+, Composer, Node 20+, dan PostgreSQL.

```bash
composer install
npm install
cp .env.example .env        # lalu isi DB_PASSWORD
php artisan key:generate
php artisan migrate --seed  # basis data jdih_ptgm harus sudah dibuat
npm run build
php artisan serve
```

Selama mengembangkan, jalankan `npm run dev` di terminal kedua sebagai ganti `npm run build`.

## Akun contoh

Dibuat oleh `php artisan migrate --seed`. Semua memakai password `password`.

| Email | Peran | Divisi |
|---|---|---|
| `admin@ptgm.test` | Admin | — |
| `gm@ptgm.test` | General Manager | — |
| `manajer.legal@ptgm.test` | Manajer | Legal |
| `karyawan.legal@ptgm.test` | Karyawan | Legal |
| `manajer.hse@ptgm.test` | Manajer | HSE |
| `karyawan.hse@ptgm.test` | Karyawan | HSE |

Ganti semua password ini sebelum aplikasi dipakai sungguhan.

## Isi otomatis dengan AI

Tombol "Isi otomatis" di form pengajuan membaca PDF dengan Google Gemini dan mengisi
kolom form. Tombol hanya muncul bila kunci diisi:

```dotenv
GEMINI_API_KEY=kunci-dari-ai.google.dev
GEMINI_MODEL=                     # kosong = bawaan di config/ai.php
AI_IZINKAN_DOKUMEN_DIVISI=false
```

- Berkas dikirim ke server Google. Bawaannya hanya pengajuan ber-akses **Publik** yang
  boleh memakai fitur ini. `AI_IZINKAN_DOKUMEN_DIVISI=true` membukanya untuk dokumen
  divisi; nyalakan hanya setelah kebijakan data penyedia disetujui perusahaan.
- Permintaan dikirim dengan `store: false`, tetapi tier gratis tidak menjamin data tidak
  dipakai penyedia.
- Nama model berubah dari waktu ke waktu. Bila muncul galat, cek model yang tersedia di
  ai.google.dev lalu isi `GEMINI_MODEL`.
- Penyedia lain: buat kelas yang mengimplementasikan `App\Services\Ai\MetadataExtractor`
  dan ganti binding di `AppServiceProvider`.

## Pindah ke MySQL / MariaDB

Skema tidak memakai fitur khusus PostgreSQL. Ubah `.env`, lalu migrasi ulang:

```dotenv
DB_CONNECTION=mysql
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate --seed
```

Tes berjalan di SQLite dalam memori, pengembangan di PostgreSQL; MySQL belum pernah
dijalankan, jadi jalankan `php artisan test` dan coba alur utama setelah berpindah.

## Tes

```bash
php artisan test
```

Bagian yang paling penting untuk dijaga tetap hijau:

- `tests/Feature/Document/VisibilitasTest.php`: siapa boleh melihat dokumen apa.
- `tests/Feature/Document/AlurTest.php`: perpindahan status dan wewenang persetujuan.

## Tempat aturan berada

| Aturan | Berkas |
|---|---|
| Siapa melihat apa (satu dokumen) | `app/Policies/DocumentPolicy.php` |
| Siapa melihat apa (daftar) | `Document::scopeVisibleTo` di `app/Models/Document.php` |
| Perpindahan status | `app/Services/DocumentWorkflow.php` |
| Validasi pengajuan dan unggahan | `app/Http/Requests/SimpanPengajuanRequest.php` |
| Warna, huruf, radius | `resources/css/app.css` |

Berkas PDF disimpan di `storage/app/private/dokumen` dan hanya dilayani lewat
`DokumenController`, yang memeriksa policy pada setiap permintaan.

## Sebelum dipakai sungguhan

- `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL` sesuai domain.
- Cadangkan basis data dan folder `storage/app/private` secara berkala.
- Batas unggah PHP (`upload_max_filesize`, `post_max_size`) minimal 20 MB.
