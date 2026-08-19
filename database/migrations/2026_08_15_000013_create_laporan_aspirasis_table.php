<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_aspirasis', function (Blueprint $table) {
            $table->bigIncrements('aspirasi_id');

            $table->string('ticket_number', 64)->unique();

            // NULLABLE — laporan dapat disampaikan anonim tanpa NIK
            $table->foreignId('warga_id')
                ->nullable()
                ->constrained('wargas')
                ->nullOnDelete();

            $table->string('judul_laporan', 150);
            $table->text('teks_keluhan');
            $table->text('lokasi_kejadian')->nullable();

            $table->enum('current_status', ['SUBMITTED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'])
                ->default('SUBMITTED');

            $table->text('catatan_tindak_lanjut')->nullable();

            $table->dateTime('submitted_at');
            $table->dateTime('resolved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite index untuk filter dashboard + sort by waktu
            $table->index(['current_status', 'submitted_at'], 'laporan_status_submitted_index');
            // Index FK
            $table->index('warga_id', 'laporan_warga_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_aspirasis');
    }
};
