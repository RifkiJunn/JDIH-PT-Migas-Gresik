# JDIH PT Gresik Migas — Spesifikasi Desain

Tanggal: 13 Oktober 2026
Status: menunggu persetujuan
Menggantikan: seluruh berkas di `../Dokumen/` (SIMDOK-*) dan `../ildis-demo.html`

## 1. Tujuan

Satu portal dengan dua ruang yang berbagi satu database:

- **Ruang publik (JDIH):** siapa pun bisa mencari, membaca, dan mengunduh dokumen
  perusahaan yang sudah disetujui untuk dibuka ke publik. Tanpa login.
- **Ruang internal:** tiap divisi menyimpan dokumennya sendiri. Dokumen masuk lewat
  pengajuan karyawan dan dua tingkat persetujuan.

Sumber keputusan: wawancara dengan pihak PT Gresik Migas (`../wawancara.m4a.txt`,
terpotong di menit 30) dan diskusi perancangan 8–13 Oktober 2026.

Ukuran berhasil:

1. Dokumen tidak pernah tampil ke publik tanpa persetujuan GM.
2. Karyawan hanya melihat dokumen publik dan dokumen divisinya sendiri.
3. Pengguna yang tidak terbiasa dengan teknologi bisa mengajukan dokumen tanpa pelatihan.
4. Setiap persetujuan, penolakan, dan unduhan tercatat.

## 2. Peran

| Peran | Lingkup | Yang bisa dilakukan |
|---|---|---|
| Guest | tanpa login | Cari, baca, unduh dokumen publik |
| Karyawan | divisinya | Semua hak Guest; lihat dokumen divisinya; ajukan dokumen |
| Manajer | divisinya | Semua hak Karyawan; setujui/tolak pengajuan divisinya (tahap 1) |
| GM | semua divisi | Lihat semua dokumen; setujui/tolak tahap akhir |
| Admin | sistem | Kelola akun, divisi, jenis dokumen; lihat log. Tidak ikut alur dokumen |

Aturan:

- Satu akun punya satu peran dan satu divisi. GM dan Admin tidak punya divisi.
- Hanya Admin yang membuat akun dan mengubah peran/divisi. Tidak ada pendaftaran mandiri.
- Manajer suatu divisi adalah akun berperan Manajer dengan divisi yang sama. Tidak ada
  tabel penugasan terpisah.
- Admin tidak bisa membuka berkas dokumen ber-akses Divisi; ia hanya melihat metadata
  dan log.

## 3. Alur dokumen

```
Draf ──ajukan──> Menunggu Manajer ──setuju──> Menunggu GM ──setuju──> Disetujui
                        │                          │
                        └────────tolak─────────────┴──> Ditolak ──perbaiki & ajukan lagi──> Menunggu Manajer
```

- Pengaju memilih **akses**: `Publik` atau `Divisi`. Divisi dokumen tidak dipilih;
  diambil dari divisi akun pengaju.
- Semua dokumen melewati dua tahap, apa pun aksesnya.
- Pengajuan oleh Manajer langsung masuk ke Menunggu GM.
- Penolakan wajib disertai catatan dan selalu kembali ke pengaju.
- Manajer dan GM tidak mengubah isi dokumen; mereka hanya menyetujui atau menolak.
- Pilihan `Publik` adalah usulan. Dokumen baru tayang setelah GM menyetujui. Layar
  persetujuan menandai usulan publik dengan jelas.
- **Dokumen yang sudah Disetujui tidak bisa diubah.** Perubahan, pencabutan, atau
  perpanjangan dilakukan dengan mengajukan dokumen baru yang direlasikan ke dokumen
  lama (bagian 5). Ini menggantikan status "Ditinjau Ulang" dan kebutuhan versioning.
- Pengaju boleh menghapus dokumen berstatus Draf atau Ditolak. Selain itu tidak ada
  penghapusan.

## 4. Siapa melihat apa

Dokumen berstatus Disetujui:

| Peran | Akses Publik | Akses Divisi |
|---|---|---|
| Guest | ya | tidak |
| Karyawan, Manajer | ya | divisinya sendiri |
| GM | ya | semua divisi |
| Admin | ya | metadata saja |

Dokumen yang belum Disetujui:

- Draf dan Ditolak: hanya pengaju.
- Menunggu Manajer: pengaju dan manajer divisinya.
- Menunggu GM: pengaju, manajer divisinya, dan GM.

Aturan ini ditulis satu kali di `DocumentPolicy` dan satu query scope, lalu dipakai
oleh semua halaman dan oleh unduhan berkas.

## 5. Data

Kolom `status`, `akses`, `peran`, dan `jenis` relasi disimpan sebagai string dan
divalidasi oleh enum PHP, bukan enum bawaan database.

**users**: name, email, password, `peran` (karyawan|manajer|gm|admin), `division_id` nullable.

**divisions**: nama.

**document_types**: nama, `perlu_kadaluarsa` (boolean).

**documents**

| Kolom | Catatan |
|---|---|
| judul, nomor | nomor unik |
| document_type_id, division_id | divisi diisi dari akun pengaju |
| akses | publik \| divisi |
| status | draf \| menunggu_manajer \| menunggu_gm \| disetujui \| ditolak |
| tanggal_penetapan | wajib saat diajukan |
| tanggal_kadaluarsa | wajib bila jenisnya `perlu_kadaluarsa` |
| abstrak, subjek | subjek opsional |
| berkas_path, berkas_nama, berkas_ukuran | satu PDF per dokumen, maks 20 MB |
| submitted_by, disetujui_pada | |
| timestamps, soft delete | |

**document_relations**: document_id, related_document_id, `jenis`
(mengubah|mencabut|menggantikan|terkait).

**approval_logs**: document_id, user_id, `aksi` (ajukan|setuju|tolak), `tahap`
(manajer|gm), catatan.

**access_logs**: document_id, user_id nullable (Guest), `aksi` (lihat|unduh), ip.

Nilai turunan, tidak disimpan:

- **Berakhir**: dokumen Disetujui dengan `tanggal_kadaluarsa` yang sudah lewat.
- **Status hukum** (Berlaku / Diubah / Dicabut / Digantikan): dari relasi dokumen lain
  yang sudah Disetujui dan menunjuk ke dokumen ini.
- **Tahun**: dari `tanggal_penetapan`.

## 6. Halaman

Satu domain. Setelah login, semua peran memakai satu kerangka internal; menu
menyesuaikan peran.

| URL | Isi | Siapa |
|---|---|---|
| `/` | Beranda JDIH: pencarian, filter jenis/tahun/subjek, daftar dokumen | semua |
| `/dokumen/{id}` | Detail: metadata, abstrak, status hukum, relasi, pratinjau dan unduh PDF | sesuai bagian 4 |
| `/login` | Satu form login. Tombol "Masuk" terlihat di kepala halaman publik | semua |
| `/arsip` | Dokumen internal yang boleh dilihat akun ini, termasuk yang akan kedaluwarsa | Karyawan, Manajer, GM |
| `/pengajuan` | Pengajuan saya dan statusnya; buat/perbaiki pengajuan | Karyawan, Manajer |
| `/persetujuan` | Antrean yang menunggu keputusan saya | Manajer, GM |
| `/admin/pengguna`, `/admin/divisi`, `/admin/jenis-dokumen`, `/admin/log` | Pengelolaan | Admin |

Setelah login: Karyawan ke `/arsip`, Manajer dan GM ke `/persetujuan`, Admin ke
`/admin/pengguna`.

## 7. Isi otomatis dengan AI

Tujuan: mengurangi ketikan saat mengajukan. AI berhenti di form; ia tidak ikut
persetujuan maupun penayangan.

- Di form pengajuan, setelah PDF dipilih, tombol **"Isi otomatis"** mengirim berkas ke
  model dan mengisi judul, nomor, jenis, tanggal penetapan, tanggal kadaluarsa, dan
  abstrak. Pengaju memeriksa dan memperbaiki sebelum menyimpan.
- Penyedia: Google Gemini, tier gratis, kunci di `GEMINI_API_KEY` pada `.env`.
- Kode pemanggil berada di balik satu interface `MetadataExtractor`, sehingga penyedia
  bisa diganti tanpa menyentuh form.
- **Batas privasi:** tier gratis tidak menjamin data tidak dipakai penyedia. Karena itu
  tombol hanya aktif untuk pengajuan ber-akses `Publik`. Untuk akses `Divisi`, tombol
  mati kecuali `AI_IZINKAN_DOKUMEN_DIVISI=true` di `.env` (bawaan `false`).
- Bila AI gagal, kehabisan kuota, atau kunci kosong: form tetap bisa diisi manual dan
  menampilkan pesan singkat. Pengajuan tidak pernah bergantung pada AI.

## 8. Teknologi

| Bagian | Pilihan |
|---|---|
| Backend | Laravel versi stabil terbaru, PHP 8.5 |
| Frontend | Inertia + React + TypeScript, Tailwind, dari starter kit resmi Laravel |
| Database | PostgreSQL 18 untuk pengembangan; harus bisa pindah ke MySQL/MariaDB |
| Berkas | Disk privat Laravel; semua unduhan lewat controller yang memeriksa policy |
| Ikon | Lucide |
| Tes | Pest |

Agar bisa pindah ke MySQL hanya dengan mengganti `.env`: tidak memakai tipe atau
operator khusus PostgreSQL (enum bawaan, `ILIKE`, jsonb); pencarian memakai
`whereLike` milik Laravel.

## 9. Arah visual

Rujukan suasana: lembaran resmi dan register arsip, bukan dasbor SaaS. `ildis-demo.html`
adalah contoh yang dihindari.

**Warna**, diambil dari logo PTGM:

- Indigo `#30318B`: warna tinta utama untuk judul, navigasi, dan tombol utama.
- Biru langit `#00A2EA`: aksen tipis untuk garis aktif, fokus, dan elemen grafis. Tidak
  dipakai untuk teks di atas putih karena kontrasnya kurang; tautan memakai indigo.
- Latar putih dan abu sangat terang; teks isi hampir hitam.
- Warna status (disetujui, menunggu, ditolak, berakhir) selalu disertai teks, tidak
  hanya warna.

**Huruf**, dipasang lokal tanpa CDN:

- Source Serif 4 untuk judul dokumen dan judul halaman.
- Public Sans untuk antarmuka, tabel, dan form; angka tabular untuk nomor dan tanggal.

**Bentuk:**

- Daftar dokumen berupa baris register dengan garis tipis, bukan kisi kartu.
- Sudut 4–6 px, tanpa bayangan kecuali pada lapisan melayang (menu, dialog).
- Ruang internal lebih rapat daripada ruang publik; keduanya memakai token yang sama.
- Gerak hanya untuk umpan balik (150–200 ms) dan menghormati `prefers-reduced-motion`.

**Tidak dipakai:** gradien, efek kaca, emoji sebagai ikon, deretan kartu statistik
berikon di beranda, hero besar dengan slogan, bayangan lembut di setiap kartu, warna
ungu, sudut sangat bulat.

**Aksesibilitas:** kontras teks minimal 4,5:1, fokus keyboard terlihat, label form
selalu tampil, pesan galat di dekat kolomnya, target sentuh minimal 44 px, layak pakai
dari lebar 375 px.

## 10. Pengujian

Tes otomatis (Pest) untuk aturan yang menyangkut keamanan:

- Visibilitas: tiap baris tabel di bagian 4, termasuk akses langsung lewat URL dan
  unduhan berkas (harus 403, bukan sekadar disembunyikan).
- Alur: setiap transisi di bagian 3, pengajuan oleh Manajer, catatan wajib saat menolak,
  dokumen Disetujui tidak bisa diubah.
- Guest tidak pernah menerima dokumen `Publik` yang belum disetujui GM.
- AI: form tetap berfungsi saat extractor gagal; tombol mati untuk akses Divisi.

Halaman pengelolaan Admin dan pencarian diuji manual.

## 11. Di luar versi pertama

- Notifikasi email jatuh tempo H-90/H-30/H-7 (versi pertama hanya daftar "akan
  kedaluwarsa" di `/arsip`).
- Berita/artikel di portal publik.
- Lebih dari satu berkas per dokumen.
- Tingkat akses "seluruh karyawan".
- Integrasi API JDIHN, tanda tangan digital, pencarian isi PDF.
- Lupa password mandiri (Admin yang mereset).

## 12. Yang belum dipastikan

- Peran kelima yang disebut klien setelah rekaman terpotong. Spesifikasi ini
  menganggap kelima peran adalah Guest, Karyawan, Manajer, GM, Admin.
- Daftar divisi dan jenis dokumen yang sebenarnya; versi pertama memakai data contoh
  yang bisa diubah Admin.
- Tenggat capstone dan jumlah anggota tim, yang menentukan apakah bagian 7 masuk
  versi pertama atau menyusul.
