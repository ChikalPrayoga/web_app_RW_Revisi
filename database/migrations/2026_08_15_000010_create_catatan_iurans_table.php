<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Membuat tabel transaksi `catatan_iurans` sesuai DATABASE_SCHEMA.md §3.10.
     * Relasi ke `kartu_keluargas.id` (internal persistence FK).
     * Menerapkan Active Unique Constraint:
     * - MySQL 8+: Generated column `guard_col` STORED + composite unique index
     * - SQLite (Testing): Native partial unique index (WHERE status != 'REJECTED' AND deleted_at IS NULL)
     */
    public function up(): void
    {
        Schema::create('catatan_iurans', function (Blueprint $table) {
            $table->id('iuran_id');
            $table->foreignId('kartu_keluarga_id')
                ->constrained('kartu_keluargas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('iuran_type_id')
                ->constrained('iuran_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->decimal('nominal', 12, 2);
            $table->unsignedTinyInteger('periode_bulan');
            $table->unsignedSmallInteger('periode_tahun');
            $table->date('tanggal_pembayaran')->nullable();
            $table->foreignId('recorded_by_user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->dateTime('approved_at')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->string('payment_proof_path', 255)->nullable();
            $table->text('rejection_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite & single indexes (DATABASE_SCHEMA.md §5.2)
            $table->index(['periode_tahun', 'periode_bulan']);
            $table->index('status');
        });

        // Driver-aware Active Unique Constraint
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("
                CREATE UNIQUE INDEX uq_catatan_iuran_active
                ON catatan_iurans (kartu_keluarga_id, iuran_type_id, periode_bulan, periode_tahun)
                WHERE status != 'REJECTED' AND deleted_at IS NULL
            ");
        } elseif ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE catatan_iurans
                ADD COLUMN guard_col TINYINT GENERATED ALWAYS AS (
                    CASE WHEN deleted_at IS NULL AND status IN ('PENDING', 'APPROVED') THEN 1 ELSE NULL END
                ) STORED
            ");
            DB::statement('
                CREATE UNIQUE INDEX uq_catatan_iuran_active
                ON catatan_iurans (kartu_keluarga_id, iuran_type_id, periode_bulan, periode_tahun, guard_col)
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS uq_catatan_iuran_active');
        }
        Schema::dropIfExists('catatan_iurans');
    }
};
