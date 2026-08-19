<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Controllers;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\CatatanIuran;
use App\Models\KasKeluar;
use App\Modules\Keuangan\Requests\ApproveCatatanIuranRequest;
use App\Modules\Keuangan\Requests\ApproveKasKeluarRequest;
use App\Modules\Keuangan\Requests\ListCatatanIuranRequest;
use App\Modules\Keuangan\Requests\ListKasKeluarRequest;
use App\Modules\Keuangan\Requests\StoreCatatanIuranRequest;
use App\Modules\Keuangan\Requests\StoreKasKeluarRequest;
use App\Modules\Keuangan\Services\KeuanganService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller Blade Monolith untuk Modul Keuangan RW.
 *
 * Mengelola seluruh antarmuka web pengurus (Ketua RT, Bendahara RW, Ketua RW, Super Admin).
 * Thin controller yang memanggil KeuanganService secara langsung.
 */
class KeuanganWebController extends Controller
{
    public function __construct(
        protected KeuanganService $keuanganService
    ) {}

    /**
     * Menampilkan daftar transaksi iuran warga.
     */
    public function indexIuran(ListCatatanIuranRequest $request): View
    {
        $this->authorize('viewAny', CatatanIuran::class);

        $user = $request->user();
        $catatanIurans = $this->keuanganService->listCatatanIuran($user, $request->validated());
        $iuranTypes = $this->keuanganService->listIuranTypes();

        return view('keuangan.iuran.index', compact('catatanIurans', 'iuranTypes'));
    }

    /**
     * Menampilkan formulir pencatatan iuran baru.
     */
    public function createIuran(): View
    {
        $this->authorize('create', CatatanIuran::class);

        $iuranTypes = $this->keuanganService->listIuranTypes();

        return view('keuangan.iuran.create', compact('iuranTypes'));
    }

    /**
     * Menyimpan data pencatatan iuran baru.
     */
    public function storeIuran(StoreCatatanIuranRequest $request): RedirectResponse
    {
        $this->authorize('create', CatatanIuran::class);

        try {
            $this->keuanganService->catatIuran(
                $request->user(),
                $request->validated(),
                $request->ip()
            );

            return redirect()
                ->route('keuangan.iuran.index')
                ->with('success', 'Pencatatan iuran berhasil disimpan dan menunggu persetujuan Bendahara RW.');
        } catch (ConflictHttpException $e) {
            return back()
                ->withInput()
                ->withErrors(['no_kk' => $e->getMessage()]);
        }
    }

    /**
     * Menampilkan halaman verifikasi/approval iuran untuk Bendahara RW.
     */
    public function approvalIuran(ListCatatanIuranRequest $request): View
    {
        $this->authorize('viewAny', CatatanIuran::class);

        $user = $request->user();
        if (! $user->hasRole(RoleName::BENDAHARA_RW->value)) {
            throw new AccessDeniedHttpException('Hanya Bendahara RW yang berwenang mengakses persetujuan iuran.');
        }

        $filters = $request->validated();
        $filters['status'] = 'PENDING';

        $pendingIurans = $this->keuanganService->listCatatanIuran($user, $filters);
        $iuranTypes = $this->keuanganService->listIuranTypes();

        return view('keuangan.iuran.approval', compact('pendingIurans', 'iuranTypes'));
    }

    /**
     * Memproses persetujuan/penolakan iuran warga.
     */
    public function processApprovalIuran(ApproveCatatanIuranRequest $request, int $id): RedirectResponse
    {
        $catatan = CatatanIuran::with('kartuKeluarga')->find($id);

        if (! $catatan) {
            throw new NotFoundHttpException('Data transaksi iuran tidak ditemukan');
        }

        $this->authorize('approve', $catatan);

        try {
            $validated = $request->validated();
            $this->keuanganService->approveIuran($request->user(), $catatan, $validated, $request->ip());

            $action = strtoupper((string) $validated['action']);
            $msg = $action === 'APPROVE'
                ? 'Transaksi iuran berhasil disetujui.'
                : 'Transaksi iuran telah ditolak.';

            return back()->with('success', $msg);
        } catch (ConflictHttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Menampilkan daftar transaksi pengeluaran kas RW.
     */
    public function indexKasKeluar(ListKasKeluarRequest $request): View
    {
        $this->authorize('viewAny', KasKeluar::class);

        $user = $request->user();
        $kasKeluars = $this->keuanganService->listKasKeluar($user, $request->validated());

        return view('keuangan.kas-keluar.index', compact('kasKeluars'));
    }

    /**
     * Menampilkan formulir pencatatan pengeluaran kas baru.
     */
    public function createKasKeluar(): View
    {
        $this->authorize('create', KasKeluar::class);

        return view('keuangan.kas-keluar.create');
    }

    /**
     * Menyimpan data pengeluaran kas RW baru.
     */
    public function storeKasKeluar(StoreKasKeluarRequest $request): RedirectResponse
    {
        $this->authorize('create', KasKeluar::class);

        $this->keuanganService->catatKasKeluar(
            $request->user(),
            $request->validated(),
            $request->ip()
        );

        return redirect()
            ->route('keuangan.kas-keluar.index')
            ->with('success', 'Pengeluaran kas berhasil dicatat dan menunggu persetujuan Ketua RW.');
    }

    /**
     * Menampilkan halaman verifikasi/approval pengeluaran kas untuk Ketua RW.
     */
    public function approvalKasKeluar(ListKasKeluarRequest $request): View
    {
        $this->authorize('viewAny', KasKeluar::class);

        $user = $request->user();
        if (! $user->hasRole(RoleName::KETUA_RW->value)) {
            throw new AccessDeniedHttpException('Hanya Ketua RW yang berwenang mengakses persetujuan pengeluaran kas.');
        }

        $filters = $request->validated();
        $filters['status'] = 'PENDING';

        $pendingKasKeluars = $this->keuanganService->listKasKeluar($user, $filters);

        return view('keuangan.kas-keluar.approval', compact('pendingKasKeluars'));
    }

    /**
     * Memproses persetujuan/penolakan pengeluaran kas RW.
     */
    public function processApprovalKasKeluar(ApproveKasKeluarRequest $request, int $id): RedirectResponse
    {
        $kasKeluar = KasKeluar::find($id);

        if (! $kasKeluar) {
            throw new NotFoundHttpException('Data pengeluaran kas tidak ditemukan');
        }

        $this->authorize('approve', $kasKeluar);

        try {
            $validated = $request->validated();
            $this->keuanganService->approveKasKeluar($request->user(), $kasKeluar, $validated, $request->ip());

            $action = strtoupper((string) $validated['action']);
            $msg = $action === 'APPROVE'
                ? 'Pengeluaran kas berhasil disetujui.'
                : 'Pengeluaran kas telah ditolak.';

            return back()->with('success', $msg);
        } catch (ConflictHttpException|AccessDeniedHttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Menampilkan rekapitulasi keuangan gabungan & iuran warga.
     */
    public function rekap(Request $request): View
    {
        $user = $request->user();
        if (! $user->hasAnyRole([
            RoleName::BENDAHARA_RW->value,
            RoleName::KETUA_RW->value,
            RoleName::KETUA_RT->value,
            RoleName::SUPER_ADMIN->value,
        ])) {
            throw new AccessDeniedHttpException('Anda tidak memiliki izin untuk melihat rekapitulasi keuangan.');
        }

        $periodeBulan = (int) $request->input('periode_bulan', now()->month);
        $periodeTahun = (int) $request->input('periode_tahun', now()->year);
        $rtCode = $request->input('rt_code');

        // Jika Ketua RT, paksa filter RT ke RT miliknya untuk rekap iuran
        if ($user->hasRole(RoleName::KETUA_RT->value)) {
            $rtCode = $user->rt_code;
        }

        $rekapGabungan = $this->keuanganService->rekapGabungan([
            'periode_bulan' => $periodeBulan,
            'periode_tahun' => $periodeTahun,
        ]);

        $rekapIuran = $this->keuanganService->rekapIuran([
            'periode_bulan' => $periodeBulan,
            'periode_tahun' => $periodeTahun,
            'rt_code' => $rtCode,
        ]);

        return view('keuangan.rekap.index', compact(
            'rekapGabungan',
            'rekapIuran',
            'periodeBulan',
            'periodeTahun',
            'rtCode'
        ));
    }
}
