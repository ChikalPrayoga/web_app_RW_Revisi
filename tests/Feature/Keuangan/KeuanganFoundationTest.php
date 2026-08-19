<?php

declare(strict_types=1);

namespace Tests\Feature\Keuangan;

use App\Enums\RoleName;
use App\Enums\StatusCatatanIuran;
use App\Enums\StatusKasKeluar;
use App\Models\CatatanIuran;
use App\Models\IuranType;
use App\Models\KartuKeluarga;
use App\Models\KasKeluar;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\IuranTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KeuanganFoundationTest extends TestCase
{
    use RefreshDatabase;

    private User $ketuaRt01;

    private User $bendaharaRw;

    private User $ketuaRw;

    private KartuKeluarga $kkRt01;

    private IuranType $iuranTypeIkk;

    private IuranType $iuranTypeKas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(IuranTypeSeeder::class);

        $roleRt = Role::where('name', RoleName::KETUA_RT->value)->firstOrFail();
        $roleBendahara = Role::where('name', RoleName::BENDAHARA_RW->value)->firstOrFail();
        $roleKetuaRw = Role::where('name', RoleName::KETUA_RW->value)->firstOrFail();

        $this->ketuaRt01 = User::factory()->create([
            'role_id' => $roleRt->id,
            'email' => 'rt01_keuangan@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => '001',
            'status' => 'ACTIVE',
        ]);

        $this->bendaharaRw = User::factory()->create([
            'role_id' => $roleBendahara->id,
            'email' => 'bendahara_keuangan@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->ketuaRw = User::factory()->create([
            'role_id' => $roleKetuaRw->id,
            'email' => 'ketuarw_keuangan@rw047.id',
            'password' => Hash::make('Password123!'),
            'rt_code' => null,
            'status' => 'ACTIVE',
        ]);

        $this->kkRt01 = KartuKeluarga::create([
            'no_kk' => '3216010101230012',
            'rt_code' => '001',
            'alamat_lengkap' => 'Jl. Kenanga No. 12, RT 001',
            'status_kepemilikan_rumah' => 'Milik Sendiri',
        ]);

        $this->iuranTypeIkk = IuranType::where('code', 'IKK')->firstOrFail();
        $this->iuranTypeKas = IuranType::where('code', 'KAS-RW')->firstOrFail();
    }

    /**
     * Test 1: Verifikasi tabel dan kolom schema iuran_types, catatan_iurans, dan kas_keluars.
     */
    public function test_schema_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('iuran_types'));
        $this->assertTrue(Schema::hasColumns('iuran_types', [
            'id', 'name', 'code', 'default_amount', 'description', 'is_active', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('catatan_iurans'));
        $this->assertTrue(Schema::hasColumns('catatan_iurans', [
            'iuran_id', 'kartu_keluarga_id', 'iuran_type_id', 'nominal', 'periode_bulan', 'periode_tahun',
            'tanggal_pembayaran', 'recorded_by_user_id', 'approved_by_user_id', 'approved_at',
            'status', 'payment_proof_path', 'rejection_notes', 'created_at', 'updated_at', 'deleted_at',
        ]));

        $this->assertTrue(Schema::hasTable('kas_keluars'));
        $this->assertTrue(Schema::hasColumns('kas_keluars', [
            'id', 'kategori', 'keterangan', 'nominal', 'tanggal_pengeluaran', 'bukti_path',
            'recorded_by_user_id', 'status', 'approved_by_user_id', 'approved_at',
            'rejection_notes', 'created_at', 'updated_at', 'deleted_at',
        ]));
    }

    /**
     * Test 2: Verifikasi IuranTypeSeeder bekerja dan bersifat idempotent.
     */
    public function test_iuran_type_seeder_is_idempotent(): void
    {
        $this->assertDatabaseHas('iuran_types', ['code' => 'IKK', 'is_active' => true]);
        $this->assertDatabaseHas('iuran_types', ['code' => 'KAS-RW', 'is_active' => true]);
        $this->assertCount(2, IuranType::all());

        // Re-run seeder
        $this->seed(IuranTypeSeeder::class);
        $this->assertCount(2, IuranType::all());
    }

    /**
     * Test 3: Model CatatanIuran creation dengan default status PENDING dan casts.
     */
    public function test_catatan_iuran_creation_default_status_and_casts(): void
    {
        $catatan = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'tanggal_pembayaran' => '2026-08-10',
            'recorded_by_user_id' => $this->ketuaRt01->id,
        ]);

        $this->assertNotNull($catatan->iuran_id);
        $this->assertEquals(StatusCatatanIuran::PENDING, $catatan->status);
        $this->assertEquals('50000.00', $catatan->nominal);
        $this->assertEquals(8, $catatan->periode_bulan);
        $this->assertEquals(2026, $catatan->periode_tahun);
        $this->assertFalse($catatan->isFinal());
    }

    /**
     * Test 4: Model Eloquent Relationships CatatanIuran, KartuKeluarga, IuranType, User.
     */
    public function test_catatan_iuran_relationships(): void
    {
        $catatan = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'tanggal_pembayaran' => '2026-08-10',
            'recorded_by_user_id' => $this->ketuaRt01->id,
            'approved_by_user_id' => $this->bendaharaRw->id,
            'approved_at' => now(),
            'status' => StatusCatatanIuran::APPROVED,
        ]);

        $this->assertInstanceOf(KartuKeluarga::class, $catatan->kartuKeluarga);
        $this->assertEquals($this->kkRt01->id, $catatan->kartuKeluarga->id);

        $this->assertInstanceOf(IuranType::class, $catatan->iuranType);
        $this->assertEquals('IKK', $catatan->iuranType->code);

        $this->assertInstanceOf(User::class, $catatan->recordedBy);
        $this->assertEquals($this->ketuaRt01->id, $catatan->recordedBy->id);

        $this->assertInstanceOf(User::class, $catatan->approvedBy);
        $this->assertEquals($this->bendaharaRw->id, $catatan->approvedBy->id);

        // Inverse relationships
        $this->assertTrue($this->kkRt01->catatanIurans->contains($catatan));
        $this->assertTrue($this->iuranTypeIkk->catatanIurans->contains($catatan));
        $this->assertTrue($this->ketuaRt01->recordedIurans->contains($catatan));
        $this->assertTrue($this->bendaharaRw->approvedIurans->contains($catatan));
    }

    /**
     * Test 5: Model KasKeluar creation, status enum, and relationships.
     */
    public function test_kas_keluar_creation_and_relationships(): void
    {
        $kasKeluar = KasKeluar::create([
            'kategori' => 'Kebersihan Lingkungan',
            'keterangan' => 'Pembelian peralatan kerja bakti warga',
            'nominal' => 350000.00,
            'tanggal_pengeluaran' => '2026-08-15',
            'bukti_path' => 'uploads/bukti/nota-1.jpg',
            'recorded_by_user_id' => $this->bendaharaRw->id,
            'status' => StatusKasKeluar::PENDING,
        ]);

        $this->assertNotNull($kasKeluar->id);
        $this->assertEquals(StatusKasKeluar::PENDING, $kasKeluar->status);
        $this->assertFalse($kasKeluar->isFinal());

        $this->assertInstanceOf(User::class, $kasKeluar->recordedBy);
        $this->assertEquals($this->bendaharaRw->id, $kasKeluar->recordedBy->id);
        $this->assertTrue($this->bendaharaRw->recordedKasKeluars->contains($kasKeluar));

        // Approve by Ketua RW
        $kasKeluar->update([
            'status' => StatusKasKeluar::APPROVED,
            'approved_by_user_id' => $this->ketuaRw->id,
            'approved_at' => now(),
        ]);

        $this->assertTrue($kasKeluar->fresh()->isFinal());
        $this->assertInstanceOf(User::class, $kasKeluar->approvedBy);
        $this->assertEquals($this->ketuaRw->id, $kasKeluar->approvedBy->id);
        $this->assertTrue($this->ketuaRw->approvedKasKeluars->contains($kasKeluar));
    }

    /**
     * Test 6: SoftDeletes pada CatatanIuran dan KasKeluar.
     */
    public function test_soft_deletes_on_catatan_iurans_and_kas_keluars(): void
    {
        $catatan = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'recorded_by_user_id' => $this->ketuaRt01->id,
        ]);

        $kasKeluar = KasKeluar::create([
            'kategori' => 'Operasional',
            'keterangan' => 'Pengeluaran operasional pos RW',
            'nominal' => 100000.00,
            'tanggal_pengeluaran' => '2026-08-15',
            'recorded_by_user_id' => $this->bendaharaRw->id,
        ]);

        $catatan->delete();
        $kasKeluar->delete();

        $this->assertSoftDeleted('catatan_iurans', ['iuran_id' => $catatan->iuran_id]);
        $this->assertSoftDeleted('kas_keluars', ['id' => $kasKeluar->id]);
    }

    /**
     * Test 7: Anti-Duplication SQLite Constraint - PENDING duplicate ditolak.
     */
    public function test_anti_duplication_pending_record_blocks_duplicate(): void
    {
        CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'status' => StatusCatatanIuran::PENDING,
            'recorded_by_user_id' => $this->ketuaRt01->id,
        ]);

        $this->expectException(QueryException::class);

        // Percobaan insert kedua dengan kombinasi KK + type + periode sama saat status PENDING
        CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'status' => StatusCatatanIuran::PENDING,
            'recorded_by_user_id' => $this->ketuaRt01->id,
        ]);
    }

    /**
     * Test 8: Anti-Duplication SQLite Constraint - APPROVED duplicate ditolak.
     */
    public function test_anti_duplication_approved_record_blocks_duplicate(): void
    {
        CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'status' => StatusCatatanIuran::APPROVED,
            'recorded_by_user_id' => $this->ketuaRt01->id,
        ]);

        $this->expectException(QueryException::class);

        // Percobaan insert kedua saat transaksi berstatus APPROVED
        CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'status' => StatusCatatanIuran::PENDING,
            'recorded_by_user_id' => $this->ketuaRt01->id,
        ]);
    }

    /**
     * Test 9: Anti-Duplication SQLite Constraint - REJECTED record memperbolehkan insert baru.
     */
    public function test_anti_duplication_rejected_record_allows_reentry(): void
    {
        $rejected = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'status' => StatusCatatanIuran::REJECTED,
            'rejection_notes' => 'Nominal keliru',
            'recorded_by_user_id' => $this->ketuaRt01->id,
        ]);

        // Insert baru untuk periode yang sama diperbolehkan karena yang lama REJECTED
        $newRecord = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'status' => StatusCatatanIuran::PENDING,
            'recorded_by_user_id' => $this->ketuaRt01->id,
        ]);

        $this->assertNotNull($newRecord->iuran_id);
        $this->assertNotEquals($rejected->iuran_id, $newRecord->iuran_id);
        $this->assertCount(2, CatatanIuran::where('kartu_keluarga_id', $this->kkRt01->id)->get());
    }

    /**
     * Test 10: Anti-Duplication SQLite Constraint - Soft-deleted record memperbolehkan insert baru.
     */
    public function test_anti_duplication_soft_deleted_record_allows_reentry(): void
    {
        $deleted = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'status' => StatusCatatanIuran::APPROVED,
            'recorded_by_user_id' => $this->ketuaRt01->id,
        ]);

        $deleted->delete();

        // Insert baru untuk periode yang sama diperbolehkan setelah transaksi lama dihapus secara logis
        $newRecord = CatatanIuran::create([
            'kartu_keluarga_id' => $this->kkRt01->id,
            'iuran_type_id' => $this->iuranTypeIkk->id,
            'nominal' => 50000.00,
            'periode_bulan' => 8,
            'periode_tahun' => 2026,
            'status' => StatusCatatanIuran::PENDING,
            'recorded_by_user_id' => $this->ketuaRt01->id,
        ]);

        $this->assertNotNull($newRecord->iuran_id);
        $this->assertNotEquals($deleted->iuran_id, $newRecord->iuran_id);
    }

    /**
     * Test 11: Kas keluar memperbolehkan banyak transaksi dengan kategori dan tanggal yang sama.
     */
    public function test_kas_keluar_allows_multiple_entries_same_category_and_date(): void
    {
        $kas1 = KasKeluar::create([
            'kategori' => 'Kebersihan',
            'keterangan' => 'Honor petugas sampah minggu ke-1',
            'nominal' => 200000.00,
            'tanggal_pengeluaran' => '2026-08-01',
            'recorded_by_user_id' => $this->bendaharaRw->id,
            'status' => StatusKasKeluar::APPROVED,
        ]);

        $kas2 = KasKeluar::create([
            'kategori' => 'Kebersihan',
            'keterangan' => 'Honor petugas sampah minggu ke-2',
            'nominal' => 200000.00,
            'tanggal_pengeluaran' => '2026-08-01',
            'recorded_by_user_id' => $this->bendaharaRw->id,
            'status' => StatusKasKeluar::PENDING,
        ]);

        $this->assertNotNull($kas1->id);
        $this->assertNotNull($kas2->id);
        $this->assertCount(2, KasKeluar::where('kategori', 'Kebersihan')->get());
    }
}
