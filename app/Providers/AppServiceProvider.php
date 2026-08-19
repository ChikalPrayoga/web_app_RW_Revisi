<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\KartuKeluarga;
use App\Models\LaporanAspirasi;
use App\Models\PengajuanSurat;
use App\Models\Warga;
use App\Observers\KartuKeluargaObserver;
use App\Observers\LaporanAspirasiObserver;
use App\Observers\PengajuanSuratObserver;
use App\Observers\WargaObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Mendaftarkan semua Model Observer untuk audit trail terpusat.
     * Sesuai AGENTS.md §4 — setiap perubahan entitas dicatat melalui Observer.
     */
    public function boot(): void
    {
        Warga::observe(WargaObserver::class);
        KartuKeluarga::observe(KartuKeluargaObserver::class);
        PengajuanSurat::observe(PengajuanSuratObserver::class);
        LaporanAspirasi::observe(LaporanAspirasiObserver::class);
    }
}
