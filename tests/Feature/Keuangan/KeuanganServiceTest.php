<?php

declare(strict_types=1);

namespace Tests\Feature\Keuangan;

use App\Enums\RoleName;
use App\Enums\StatusCatatanIuran;
use App\Enums\StatusKasKeluar;
use App\Models\IuranType;
use App\Models\KartuKeluarga;
use App\Models\Role;
use App\Models\User;
use App\Modules\Keuangan\Services\KeuanganService;
use Database\Seeders\IuranTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class KeuanganServiceTest extends TestCase
{
    use RefreshDatabase;

    private KeuanganService $service;

    private User $ketuaRt01;

    private User $ketuaRt02;

    private User $bendaharaRw;

    private User $ketuaRw;

    private KartuKeluarga $kkRt01;

    private KartuKeluarga $kkRt02;

    private IuranType $iuranTypeIkk;

    private IuranType $iuranTypeKas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(IuranTypeSeeder::class);

        $this->service = app(KeuanganService::class);

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $roleBendahara = Role::where('name', RoleName::BENDAHARA_RW->value)->firstOrFail();
        $roleKetuaRw = Role::where('name', RoleName::KETUA_RW->value)->firstOrFail();

        $this->ketuaRt01 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt01_serv@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRt02 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt02_serv@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '002',
            'status' => 'ACTIVE',
        ]);

        $this->bendaharaRw = User::factory()->create([
            'role_id' => $roleBendahara->id,
            'email' => 'bendahara_serv@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleKetuaRw->id,
            'email' => 'ketuarw_serv@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->kkRt01 = KartuKeluarga::create([
            'no_kk' => '3216010101230012',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Mawar No. 12, RT 001',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $this->kkRt02 = KartuKeluarga::create([
            'no_kk' => '3216010101230099',
            'rt_code' => '002',
            'alamat_lengkap' => 'Jl. Melati No. 99, RT 002',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $this->iuranTypeIkk = IuranType::where('code', 'IKK')->firstOrFail();
        $this->iuranTypeKas = IuranType::where('code', 'KAS-RW')->firstOrFail();
    }

    public function test_catat_iuran_valid(): void
    {
        $catatan = $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'tanggal_pembayaran' => '2026-08-10',
        ]);

        $this->assertNotNull($catatan->iuran_id);
        $this->assertEquals(StatusCatatanIuran::PENDING, $catatan->status);
        $this->assertEquals($this->kkRt01->id, $catatan->kartu_keluarga_id);
        $this->assertEquals($this->ketuaRt01->id, $catatan->recorded_by_user_id);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Keuangan',
            'action' => 'CREATE_IURAN',
            'entity_id' => (string) $catatan->iuran_id,
        ]);
    }

    public function test_catat_iuran_kk_tidak_ditemukan(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '9999999999999999',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);
    }

    public function test_catat_iuran_kk_beda_rt(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        // Ketua RT 001 mencoba mencatat untuk KK RT 002
        $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '3216010101230099',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);
    }

    public function test_catat_iuran_duplicate_pending_ditolak(): void
    {
        $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        $this->expectException(ConflictHttpException::class);

        $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);
    }

    public function test_catat_iuran_duplicate_approved_ditolak(): void
    {
        $catatan = $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        $this->service->approveIuran($this->bendaharaRw, $catatan, [
            'action' => 'APPROVE',
        ]);

        $this->expectException(ConflictHttpException::class);

        $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);
    }

    public function test_catat_iuran_rejected_allows_reentry(): void
    {
        $catatan = $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        $this->service->approveIuran($this->bendaharaRw, $catatan, [
            'action' => 'REJECT',
            'rejection_notes' => 'Nominal keliru',
        ]);

        // Re-entry allowed
        $newCatatan = $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        $this->assertNotNull($newCatatan->iuran_id);
        $this->assertNotEquals($catatan->iuran_id, $newCatatan->iuran_id);
    }

    public function test_approve_iuran_success(): void
    {
        $catatan = $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        $approved = $this->service->approveIuran($this->bendaharaRw, $catatan, [
            'action' => 'APPROVE',
        ]);

        $this->assertEquals(StatusCatatanIuran::APPROVED, $approved->status);
        $this->assertEquals($this->bendaharaRw->id, $approved->approved_by_user_id);
        $this->assertNotNull($approved->approved_at);
        $this->assertTrue($approved->isFinal());

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Keuangan',
            'action' => 'APPROVE_IURAN',
            'entity_id' => (string) $catatan->iuran_id,
        ]);
    }

    public function test_reject_iuran_requires_notes(): void
    {
        $catatan = $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->approveIuran($this->bendaharaRw, $catatan, [
            'action' => 'REJECT',
            'rejection_notes' => '',
        ]);
    }

    public function test_approve_iuran_final_state_cannot_be_modified(): void
    {
        $catatan = $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        $this->service->approveIuran($this->bendaharaRw, $catatan, [
            'action' => 'APPROVE',
        ]);

        $this->expectException(ConflictHttpException::class);

        // Percobaan mengubah status yang sudah APPROVED
        $this->service->approveIuran($this->bendaharaRw, $catatan->fresh(), [
            'action' => 'REJECT',
            'rejection_notes' => 'Coba ubah status final',
        ]);
    }

    public function test_catat_kas_keluar_valid(): void
    {
        $kas = $this->service->catatKasKeluar($this->bendaharaRw, [
            'kategori' => 'Kebersihan',
            'keterangan' => 'Pembelian plastik sampah besar RW',
            'nominal' => 150000.00,
            'tanggal_pengeluaran' => '2026-08-15',
        ]);

        $this->assertNotNull($kas->id);
        $this->assertEquals(StatusKasKeluar::PENDING, $kas->status);
        $this->assertEquals($this->bendaharaRw->id, $kas->recorded_by_user_id);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'Keuangan',
            'action' => 'CREATE_KAS_KELUAR',
            'entity_id' => (string) $kas->id,
        ]);
    }

    public function test_approve_kas_keluar_success(): void
    {
        $kas = $this->service->catatKasKeluar($this->bendaharaRw, [
            'kategori' => 'Kebersihan',
            'keterangan' => 'Pembelian plastik sampah besar RW',
            'nominal' => 150000.00,
            'tanggal_pengeluaran' => '2026-08-15',
        ]);

        $approved = $this->service->approveKasKeluar($this->ketuaRw, $kas, [
            'action' => 'APPROVE',
        ]);

        $this->assertEquals(StatusKasKeluar::APPROVED, $approved->status);
        $this->assertEquals($this->ketuaRw->id, $approved->approved_by_user_id);
        $this->assertNotNull($approved->approved_at);
        $this->assertTrue($approved->isFinal());
    }

    public function test_approve_kas_keluar_anti_self_approval(): void
    {
        // Bendahara mencoba menyetujui transaksi kas keluar yang ia catat sendiri
        $kas = $this->service->catatKasKeluar($this->bendaharaRw, [
            'kategori' => 'Kebersihan',
            'keterangan' => 'Pembelian kantong sampah besar',
            'nominal' => 100000.00,
            'tanggal_pengeluaran' => '2026-08-15',
        ]);

        $this->expectException(AccessDeniedHttpException::class);

        $this->service->approveKasKeluar($this->bendaharaRw, $kas, [
            'action' => 'APPROVE',
        ]);
    }

    public function test_rekap_iuran_and_gabungan(): void
    {
        // 1. Iuran APPROVED
        $catatan1 = $this->service->catatIuran($this->ketuaRt01, [
            'no_kk' => '3216010101230012',
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);
        $this->service->approveIuran($this->bendaharaRw, $catatan1, ['action' => 'APPROVE']);

        // 2. Iuran PENDING (tidak boleh masuk rekap)
        $this->service->catatIuran($this->ketuaRt02, [
            'no_kk' => '3216010101230099',
            'iuran_type_id' => $this->iuranTypeKas->id,
            'nominal' => 25000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        // 3. Kas Keluar APPROVED
        $kas1 = $this->service->catatKasKeluar($this->bendaharaRw, [
            'kategori' => 'Kebersihan',
            'keterangan' => 'Honor petugas kebersihan RW',
            'nominal' => 20000.00,
            'tanggal_pengeluaran' => '2026-08-15',
        ]);
        $this->service->approveKasKeluar($this->ketuaRw, $kas1, ['action' => 'APPROVE']);

        // 4. Kas Keluar PENDING (tidak boleh masuk rekap)
        $this->service->catatKasKeluar($this->bendaharaRw, [
            'kategori' => 'Operasional',
            'keterangan' => 'Beli amplop kantor RW',
            'nominal' => 10000.00,
            'tanggal_pengeluaran' => '2026-08-15',
        ]);

        // Test Rekap Iuran
        $rekapIuran = $this->service->rekapIuran([
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        $this->assertEquals(2, $rekapIuran['total_kk_wajib_bayar']);
        $this->assertEquals(1, $rekapIuran['total_kk_sudah_bayar']);
        $this->assertEquals(50000.00, $rekapIuran['total_nominal_terkumpul']);

        // Test Rekap Gabungan
        $rekapGabungan = $this->service->rekapGabungan([
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
        ]);

        $this->assertEquals(50000.00, $rekapGabungan['total_pemasukan']);
        $this->assertEquals(20000.00, $rekapGabungan['total_pengeluaran']);
        $this->assertEquals(30000.00, $rekapGabungan['saldo_akhir']);
    }
}
