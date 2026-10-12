<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->timestamps();
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            $table->boolean('perlu_kadaluarsa')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('peran', 20)->default('karyawan');
            $table->foreignId('division_id')->nullable()->constrained()->restrictOnDelete();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            // Keunikan nomor dijaga validasi (mengabaikan yang terhapus), bukan indeks unik.
            $table->string('nomor', 100)->index();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('division_id')->constrained()->restrictOnDelete();
            $table->string('akses', 20);
            $table->string('status', 30)->default('draf');
            $table->date('tanggal_penetapan');
            $table->date('tanggal_kadaluarsa')->nullable()->index();
            $table->text('abstrak');
            $table->string('subjek', 150)->nullable();
            $table->string('berkas_path');
            $table->string('berkas_nama');
            $table->unsignedInteger('berkas_ukuran');
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('disetujui_pada')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'akses']);
        });

        Schema::create('document_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('jenis', 20);
            $table->timestamps();
        });

        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('aksi', 20);
            $table->string('tahap', 20);
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('aksi', 20);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('approval_logs');
        Schema::dropIfExists('document_relations');
        Schema::dropIfExists('documents');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('division_id');
            $table->dropColumn('peran');
        });
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('divisions');
    }
};
