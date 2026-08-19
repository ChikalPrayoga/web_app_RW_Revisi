<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wargas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kartu_keluarga_id')
                ->constrained('kartu_keluargas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->text('nik'); // AES-256-CBC Encrypted PII Payload
            $table->string('nik_hash', 64)->unique(); // Deterministic HMAC-SHA256 Lookup Identity
            $table->text('no_kk'); // AES-256-CBC Encrypted PII Payload
            $table->string('nama_lengkap', 100)->index();
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->text('tempat_lahir'); // AES-256-CBC Encrypted PII Payload
            $table->date('tanggal_lahir');
            $table->string('pekerjaan', 100)->nullable();
            $table->text('nomor_hp')->nullable(); // AES-256-CBC Encrypted PII Payload
            $table->string('status_hubungan_keluarga', 50);
            $table->string('status_sosio_ekonomi', 50)->nullable();
            $table->string('status_warga', 50)->default('TETAP');
            $table->enum('verification_status', ['MENUNGGU_VERIFIKASI', 'TERVERIFIKASI', 'DITOLAK'])
                ->default('MENUNGGU_VERIFIKASI');
            $table->foreignId('verified_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('verification_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wargas');
    }
};
