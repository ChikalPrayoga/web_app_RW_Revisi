<?php

declare(strict_types=1);

namespace App\Modules\Persuratan\Controllers;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\PengajuanSurat;
use App\Modules\Persuratan\Requests\ListPengajuanSuratRequest;
use App\Modules\Persuratan\Requests\StorePengajuanSuratRequest;
use App\Modules\Persuratan\Requests\VerifyPengajuanSuratRequest;
use App\Modules\Persuratan\Services\SuratService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Web Controller untuk Modul Persuratan (Blade Views).
 *
 * Thin controller — business logic sepenuhnya di SuratService.
 * Public: form pengajuan dan halaman tracking (tidak perlu login).
 * Protected: daftar dan verifikasi (memerlukan RBAC).
 *
 * JANGAN melakukan fetch ke REST API internal — panggil SuratService langsung.
 *
 * @see app/Modules/Persuratan/Services/SuratService.php
 * @see app/Modules/Kependudukan/Controllers/KependudukanWebController.php (pola referensi)
 */
class PersuratanWebController extends Controller
{
    public function __construct(
        protected SuratService $suratService
    ) {}

    // =========================================================================
    // PUBLIC — Pengajuan Surat (Tanpa Login)
    // =========================================================================

    /**
     * GET /surat/ajukan — Tampilkan form pengajuan surat (guest).
     */
    public function createForm(): View
    {
        return view('persuratan.create');
    }

    /**
     * POST /surat/ajukan — Proses pengajuan surat baru (guest).
     */
    public function store(StorePengajuanSuratRequest $request): RedirectResponse
    {
        try {
            $pengajuan = $this->suratService->submitPengajuan($request->validated());

            return redirect()
                ->route('persuratan.public.success', ['tracking_code' => $pengajuan->tracking_code])
                ->with('success', 'Pengajuan surat berhasil dikirim!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors());
        }
    }

    /**
     * GET /surat/sukses/{tracking_code} — Halaman konfirmasi berhasil submit.
     */
    public function success(string $tracking_code): View
    {
        $pengajuan = $this->suratService->trackByKode($tracking_code);

        return view('persuratan.success', compact('pengajuan'));
    }

    // =========================================================================
    // PUBLIC — Tracking Surat (Tanpa Login)
    // =========================================================================

    /**
     * GET /surat/lacak — Form input tracking code.
     */
    public function trackForm(): View
    {
        return view('persuratan.track');
    }

    /**
     * GET /surat/lacak/{tracking_code} — Tampilkan hasil tracking.
     */
    public function trackResult(string $tracking_code): View|RedirectResponse
    {
        try {
            $pengajuan = $this->suratService->trackByKode($tracking_code);

            // Ambil riwayat dari audit_logs
            $riwayat = \App\Models\AuditLog::where('entity_type', 'pengajuan_surats')
                ->where('entity_id', (string) $pengajuan->pengajuan_id)
                ->orderBy('created_at', 'asc')
                ->get();

            return view('persuratan.track_result', compact('pengajuan', 'riwayat'));
        } catch (NotFoundHttpException) {
            return redirect()
                ->route('persuratan.public.track')
                ->withErrors(['tracking_code' => 'Kode pelacakan tidak ditemukan. Periksa kembali kode yang Anda masukkan.']);
        }
    }

    // =========================================================================
    // PROTECTED — Pengurus (Memerlukan Auth + RBAC)
    // =========================================================================

    /**
     * GET /surat — Daftar pengajuan surat (pengurus).
     * Policy: PengajuanSuratPolicy::viewAny()
     */
    public function index(ListPengajuanSuratRequest $request): View
    {
        $this->authorize('viewAny', PengajuanSurat::class);

        $user = $request->user();
        $pengajuans = $this->suratService->listPengajuan($user, $request->validated());

        return view('persuratan.index', compact('pengajuans'));
    }

    /**
     * GET /surat/{id} — Detail pengajuan surat (pengurus).
     * Policy: PengajuanSuratPolicy::view($pengajuan)
     */
    public function show(Request $request, int $id): View
    {
        $pengajuan = PengajuanSurat::with(['warga.kartuKeluarga'])->find($id);

        if (! $pengajuan) {
            throw new NotFoundHttpException('Pengajuan surat tidak ditemukan');
        }

        $this->authorize('view', $pengajuan);

        // Ambil riwayat dari audit_logs untuk ditampilkan di detail
        $riwayat = \App\Models\AuditLog::where('entity_type', 'pengajuan_surats')
            ->where('entity_id', (string) $pengajuan->pengajuan_id)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('persuratan.show', compact('pengajuan', 'riwayat'));
    }

    /**
     * GET /surat/{id}/verifikasi — Form verifikasi pengajuan (pengurus).
     * Policy: PengajuanSuratPolicy::verify($pengajuan)
     */
    public function verifyForm(Request $request, int $id): View
    {
        $pengajuan = PengajuanSurat::with(['warga.kartuKeluarga'])->find($id);

        if (! $pengajuan) {
            throw new NotFoundHttpException('Pengajuan surat tidak ditemukan');
        }

        $this->authorize('verify', $pengajuan);

        return view('persuratan.verify', compact('pengajuan'));
    }

    /**
     * POST /surat/{id}/verifikasi — Proses keputusan verifikasi (pengurus).
     * Policy: PengajuanSuratPolicy::verify($pengajuan)
     */
    public function verify(VerifyPengajuanSuratRequest $request, int $id): RedirectResponse
    {
        $pengajuan = PengajuanSurat::with(['warga.kartuKeluarga'])->find($id);

        if (! $pengajuan) {
            throw new NotFoundHttpException('Pengajuan surat tidak ditemukan');
        }

        $this->authorize('verify', $pengajuan);

        if ($pengajuan->isFinal()) {
            return redirect()
                ->route('persuratan.show', $id)
                ->with('error', 'Pengajuan ini sudah berstatus final ('.$pengajuan->current_status->value.') dan tidak dapat diproses ulang');
        }

        try {
            $user = $request->user();

            if ($user->hasRole(RoleName::KETUA_RT->value)) {
                $this->suratService->reviewByRt($user, $pengajuan, $request->validated());
            } else {
                $this->suratService->verifyByRw($user, $pengajuan, $request->validated());
            }

            return redirect()
                ->route('persuratan.show', $id)
                ->with('success', 'Status pengajuan surat berhasil diperbarui');
        } catch (ConflictHttpException $e) {
            return redirect()
                ->route('persuratan.show', $id)
                ->with('error', $e->getMessage());
        }
    }
}
