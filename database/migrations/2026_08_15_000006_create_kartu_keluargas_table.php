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
        Schema::create('kartu_keluargas', function (Blueprint $table) {
            $table->id();
            $table->text('no_kk'); // AES-256-CBC Encrypted PII Payload
            $table->string('no_kk_hash', 64)->unique(); // Deterministic HMAC-SHA256 Lookup Identity
            $table->string('rt_code', 20)->index();
            $table->text('alamat_lengkap'); // AES-256-CBC Encrypted PII Payload
            $table->string('blok', 20)->nullable();
            $table->string('nomor_rumah', 20)->nullable();
            $table->string('status_kepemilikan_rumah', 50);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kartu_keluargas');
    }
};
