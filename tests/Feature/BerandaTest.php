<?php

use App\Enums\StatusDokumen;
use App\Models\Document;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function idBeranda(string $query = ''): array
{
    return array_column(test()->get('/dokumen'.$query)->assertOk()->viewData('page')['props']['dokumen']['data'], 'id');
}

it('hanya memuat dokumen publik yang disetujui, juga saat pengunjung sedang login', function () {
    $publik = Document::factory()->disetujui()->publik()->create();
    $divisi = Document::factory()->disetujui()->create();
    Document::factory()->publik()->status(StatusDokumen::MenungguGm)->create();

    expect(idBeranda())->toBe([$publik->id]);

    // GM boleh melihat dokumen divisi di arsip, tetapi beranda tetap versi publik.
    $this->actingAs(User::factory()->gm()->create());
    expect(idBeranda())->toBe([$publik->id]);
    $this->actingAs(User::factory()->karyawan($divisi->division)->create());
    expect(idBeranda())->toBe([$publik->id]);
});

it('mencari pada judul dan nomor', function () {
    $a = Document::factory()->disetujui()->publik()->create(['judul' => 'Kebijakan Keselamatan Kerja', 'nomor' => 'SK-DIR/012/2023']);
    Document::factory()->disetujui()->publik()->create(['judul' => 'Tata Kelola Perusahaan', 'nomor' => 'PP/001/2021']);

    expect(idBeranda('?q=keselamatan'))->toBe([$a->id])->and(idBeranda('?q=sk-dir'))->toBe([$a->id]);
});

it('menggabungkan filter jenis, tahun, dan subjek', function () {
    $a = Document::factory()->disetujui()->publik()->create(['tanggal_penetapan' => '2023-01-10', 'subjek' => 'K3LL']);
    $b = Document::factory()->disetujui()->publik()->create(['tanggal_penetapan' => '2024-01-10', 'subjek' => 'K3LL', 'document_type_id' => $a->document_type_id]);

    expect(idBeranda("?jenis={$a->document_type_id}"))->toEqualCanonicalizing([$a->id, $b->id])
        ->and(idBeranda("?jenis={$a->document_type_id}&tahun=2024"))->toBe([$b->id])
        ->and(idBeranda('?subjek=K3LL&tahun=2023'))->toBe([$a->id])
        ->and(idBeranda('?tahun=1999'))->toBe([]);
});

it('pilihan filter hanya berasal dari dokumen publik', function () {
    Document::factory()->disetujui()->publik()->create(['tanggal_penetapan' => '2023-01-10', 'subjek' => 'K3LL']);
    Document::factory()->disetujui()->create(['tanggal_penetapan' => '2019-01-10', 'subjek' => 'Rahasia Dagang']);

    $this->get('/dokumen')->assertInertia(fn (AssertableInertia $page) => $page
        ->component('dokumen/index')->where('pilihan.tahun', [2023])->where('pilihan.subjek', ['K3LL'])->has('pilihan.jenis', 1));
});

it('mengabaikan parameter filter yang tidak sah', function () {
    Document::factory()->disetujui()->publik()->create();

    $this->get('/dokumen?tahun=abc&jenis[]=1&q[]=x')->assertOk();
});

it('membagi hasil per 20 dokumen', function () {
    Document::factory()->count(21)->disetujui()->publik()->create();

    $this->get('/dokumen')->assertInertia(fn (AssertableInertia $page) => $page->has('dokumen.data', 20)->where('dokumen.total', 21));
});

it('beranda meringkas hanya dokumen publik yang disetujui', function () {
    $lama = Document::factory()->disetujui()->publik()->create(['tanggal_penetapan' => '2021-02-01']);
    $baru = Document::factory()->disetujui()->publik()->create(['tanggal_penetapan' => '2024-06-01', 'document_type_id' => $lama->document_type_id]);
    Document::factory()->disetujui()->create(['tanggal_penetapan' => '2025-01-01']);
    Document::factory()->publik()->status(StatusDokumen::MenungguGm)->create();

    $this->actingAs(User::factory()->gm()->create())->get('/')->assertInertia(fn (AssertableInertia $page) => $page
        ->component('beranda')
        ->where('total', 2)
        ->has('terbaru', 2)->where('terbaru.0.id', $baru->id)
        ->has('jenisPublik', 1)->where('jenisPublik.0.jumlah', 2)
        ->where('tahun', [2024, 2021]));
});

it('beranda menampilkan paling banyak lima dokumen terbaru dan tetap terbuka saat kosong', function () {
    $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page->where('total', 0)->has('terbaru', 0)->has('jenisPublik', 0));

    Document::factory()->count(7)->disetujui()->publik()->create();
    $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page->has('terbaru', 5)->where('total', 7));
});

it('menu publik hanya memuat jenis yang punya dokumen publik', function () {
    $publik = Document::factory()->disetujui()->publik()->create();
    Document::factory()->disetujui()->create();

    $this->get('/tentang')->assertInertia(fn (AssertableInertia $page) => $page
        ->component('tentang')->has('jenisPublik', 1)->where('jenisPublik.0.id', $publik->document_type_id));
});

it('menyaring menurut nomor, status hukum, dan mengurutkan', function () {
    $lama = Document::factory()->disetujui()->publik()->create(['nomor' => 'PP/001/2021', 'judul' => 'B lama', 'tanggal_penetapan' => '2021-01-01']);
    $baru = Document::factory()->disetujui()->publik()->create(['nomor' => 'PP/004/2024', 'judul' => 'A baru', 'tanggal_penetapan' => '2024-01-01']);
    $habis = Document::factory()->disetujui()->publik()->create(['nomor' => 'SK/9/2020', 'judul' => 'C habis', 'tanggal_penetapan' => '2020-01-01', 'tanggal_kadaluarsa' => '2022-01-01']);
    $baru->relasiKeluar()->create(['related_document_id' => $lama->id, 'jenis' => 'mengubah']);

    expect(idBeranda('?nomor=pp/00'))->toBe([$baru->id, $lama->id])
        ->and(idBeranda('?status=diubah'))->toBe([$lama->id])
        ->and(idBeranda('?status=berlaku'))->toBe([$baru->id])
        ->and(idBeranda('?status=berakhir'))->toBe([$habis->id])
        ->and(idBeranda('?status=dicabut'))->toBe([])
        ->and(idBeranda('?urut=terlama'))->toBe([$habis->id, $lama->id, $baru->id])
        ->and(idBeranda('?urut=judul'))->toBe([$baru->id, $lama->id, $habis->id]);
});

it('daftar dokumen memuat jumlah dilihat dan diunduh serta jumlah per jenis', function () {
    $dok = Document::factory()->disetujui()->publik()->create();
    $dok->accessLogs()->createMany([['aksi' => 'lihat', 'ip' => '1.1.1.1'], ['aksi' => 'lihat', 'ip' => '1.1.1.1'], ['aksi' => 'unduh', 'ip' => '1.1.1.1']]);

    $this->get('/dokumen')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('dokumen.data.0.dilihat', 2)->where('dokumen.data.0.diunduh', 1)->where('pilihan.jenis.0.jumlah', 1));
});
