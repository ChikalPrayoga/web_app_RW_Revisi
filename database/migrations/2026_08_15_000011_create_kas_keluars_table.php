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
     * Membuat tabel transaksi `kas_keluars` sesuai DATABASE_SCHEMA.md §3.11.
     * Relasi ke `users` (pencatat: Bendahara RW, penyetuju: Ketua RW).
     * Tanpa relasi KK/Warga — murni transaksi operasional kas RW.
     */
    public function up(): void
    {
        Schema::create('kas_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('kategori', 100);
            $table->text('keterangan');
            $table->decimal('nominal', 12, 2);
            $table->date('tanggal_pengeluaran');
            $table->string('bukti_path', 255)->nullable();
            $table->foreignId('recorded_by_user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->dateTime('approved_at')->nullable();
            $table->text('rejection_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes (DATABASE_SCHEMA.md §5.2)
            $table->index('recorded_by_user_id');
            $table->index('approved_by_user_id');
            $table->index('status');
            $table->index('tanggal_pengeluaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_keluars');
    }
};
