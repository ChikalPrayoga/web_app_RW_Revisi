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
     * Tabel informasi_publiks menyimpan pengumuman, berita, dan agenda kegiatan RW 047.
     *
     * @see docs/DATABASE_SCHEMA.md §3.12
     */
    public function up(): void
    {
        Schema::create('informasi_publiks', function (Blueprint $table): void {
            $table->id();
            $table->string('judul', 150);
            $table->text('konten');
            $table->enum('kategori', ['PENGUMUMAN', 'BERITA', 'AGENDA']);
            $table->date('tanggal_publikasi');
            $table->date('tanggal_agenda')->nullable();
            $table->foreignId('published_by_user_id')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->enum('status', ['DRAFT', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->timestamps();
            $table->softDeletes();

            // Indexes sesuai DATABASE_SCHEMA.md §5.3
            $table->index(['status', 'tanggal_publikasi'], 'informasi_publiks_status_tanggal_index');
            $table->index('kategori', 'informasi_publiks_kategori_index');
            $table->index('published_by_user_id', 'informasi_publiks_published_by_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informasi_publiks');
    }
};
