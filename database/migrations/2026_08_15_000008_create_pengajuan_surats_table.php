<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Membuat tabel `pengajuan_surats` sesuai DATABASE_SCHEMA.md §3.7.
     * Relasi ke `wargas.id` (surrogate key) sebagai penghubung pemohon.
     * Kolom `tracking_code` menjadi identifier publik untuk pelacakan (UNIQUE).
     * SoftDeletes diterapkan — hard-delete dilarang (RULES.md §2.5).
     */
    public function up(): void
    {
        Schema::create('pengajuan_surats', function (Blueprint $table) {
            $table->id('pengajuan_id'); // PK sesuai DATABASE_SCHEMA.md §3.7
            $table->string('tracking_code', 64)->unique()->index(); // Identifier publik pelacakan
            $table->foreignId('warga_id')
                ->constrained('wargas')
                ->restrictOnDelete()
                ->cascadeOnUpdate(); // FK → wargas.id (One-to-Many via warga pemohon)
            $table->string('nomor_surat', 100)->nullable(); // Diterbitkan saat status COMPLETED
            $table->enum('jenis_surat', ['SURAT_PENGANTAR', 'SURAT_KETERANGAN_DOMISILI']); // Sesuai DATABASE_SCHEMA.md §3.7
            $table->text('keperluan'); // Alasan permohonan surat
            $table->enum('current_status', [
                'SUBMITTED',
                'RT_REVIEW',
                'RW_REVIEW',
                'COMPLETED',
                'REJECTED',
            ])->default('SUBMITTED'); // Sesuai DATABASE_SCHEMA.md §3.7 + API_SPECIFICATION.md §3.4
            $table->text('catatan_penolakan')->nullable(); // Alasan penolakan saat status REJECTED (RT/RW)
            $table->dateTime('tanggal_pengajuan'); // Waktu formulir dikirim
            $table->dateTime('tanggal_selesai')->nullable(); // Waktu selesai disetujui/ditolak
            $table->timestamps();
            $table->softDeletes(); // Wajib — hard-delete dilarang (RULES.md §2.5)

            // Composite index untuk query dashboard pengurus (DATABASE_SCHEMA.md §5.2)
            $table->index(['current_status', 'tanggal_pengajuan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_surats');
    }
};
