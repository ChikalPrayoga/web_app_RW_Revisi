<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\CatatanIuran;
use App\Models\InformasiPublik;
use App\Models\KartuKeluarga;
use App\Models\KasKeluar;
use App\Models\LaporanAspirasi;
use App\Models\PengajuanSurat;
use App\Models\Warga;
use App\Modules\InformasiPublik\Policies\InformasiPublikPolicy;
use App\Modules\Kependudukan\Policies\KartuKeluargaPolicy;
use App\Modules\Kependudukan\Policies\WargaPolicy;
use App\Modules\Keuangan\Policies\CatatanIuranPolicy;
use App\Modules\Keuangan\Policies\KasKeluarPolicy;
use App\Modules\LaporanAspirasi\Policies\LaporanAspirasiPolicy;
use App\Modules\Persuratan\Policies\PengajuanSuratPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        KartuKeluarga::class => KartuKeluargaPolicy::class,
        Warga::class => WargaPolicy::class,
        PengajuanSurat::class => PengajuanSuratPolicy::class,
        CatatanIuran::class => CatatanIuranPolicy::class,
        KasKeluar::class => KasKeluarPolicy::class,
        InformasiPublik::class => InformasiPublikPolicy::class,
        LaporanAspirasi::class => LaporanAspirasiPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
