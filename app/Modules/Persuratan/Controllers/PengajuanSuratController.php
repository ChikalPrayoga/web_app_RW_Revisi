<?php

declare(strict_types=1);

namespace App\Modules\Persuratan\Controllers;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\PengajuanSurat;
use App\Modules\Persuratan\Requests\ListPengajuanSuratRequest;
use App\Modules\Persuratan\Requests\StorePengajuanSuratRequest;
use App\Modules\Persuratan\Requests\VerifyPengajuanSuratRequest;
use App\Modules\Persuratan\Resources\PengajuanSuratResource;
use App\Modules\Persuratan\Resources\PengajuanSuratTrackResource;
use App\Modules\Persuratan\Services\SuratService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * REST API Controller untuk Modul Persuratan.
 *
 * Thin controller — business logic sepenuhnya di SuratService.
 * Public endpoints: submit dan track (tidak memerlukan autentikasi).
 * Protected endpoints: list dan verify (memerlukan RBAC via Policy + Service).
 *
 * @see API_SPECIFICATION.md §3.4
 * @see app/Modules/Persuratan/Services/SuratService.php
 */
class PengajuanSuratController extends Controller
{
    public function __construct(
        protected SuratService $suratService
    ) {}

    /**
     * POST /api/v1/surat/pengajuan
     *
     * Akses: PUBLIC (tanpa autentikasi).
     * Warga mengajukan surat baru menggunakan NIK, jenis surat, dan keperluan.
     * NIK di-hash untuk lookup — tidak tersimpan di pengajuan_surats.
     *
     * @see API_SPECIFICATION.md §3.4.1
     */
    public function store(StorePengajuanSuratRequest $request): JsonResponse
    {
        $pengajuan = $this->suratService->submitPengajuan($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan surat berhasil dikirim',
            'data' => [
                'tracking_code' => $pengajuan->tracking_code,
                'jenis_surat' => $pengajuan->jenis_surat?->value,
                'current_status' => $pengajuan->current_status?->value,
                'tanggal_pengajuan' => $pengajuan->tanggal_pengajuan?->toDateTimeString(),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/surat/pengajuan/track/{tracking_code}
     *
     * Akses: PUBLIC (tanpa autentikasi).
     * Mengembalikan status dan riwayat perubahan status — tanpa PII.
     *
     * @see API_SPECIFICATION.md §3.4.2
     */
    public function track(string $tracking_code): JsonResponse
    {
        $pengajuan = $this->suratService->trackByKode($tracking_code);

        return response()->json([
            'success' => true,
            'message' => 'Data pengajuan ditemukan',
            'data' => new PengajuanSuratTrackResource($pengajuan),
        ], Response::HTTP_OK);
    }

    /**
     * GET /api/v1/surat/pengajuan
     *
     * Akses: KETUA_RT, SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.
     * KETUA_RT hanya melihat wilayah RT-nya (area scoping di Service Layer).
     *
     * @see API_SPECIFICATION.md §3.4.3
     */
    public function index(ListPengajuanSuratRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PengajuanSurat::class);

        $user = $request->user();
        $paginator = $this->suratService->listPengajuan($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengajuan surat berhasil diambil',
            'data' => PengajuanSuratResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * POST /api/v1/surat/pengajuan/{id}/verify
     *
     * Akses: KETUA_RT, SEKRETARIS_RW, KETUA_RW.
     * Routing ke reviewByRt() atau verifyByRw() berdasarkan role user.
     * Validasi status transition dan area scoping dilakukan di SuratService.
     *
     * @see API_SPECIFICATION.md §3.4.4
     */
    public function verify(VerifyPengajuanSuratRequest $request, int $id): JsonResponse
    {
        $pengajuan = PengajuanSurat::with(['warga.kartuKeluarga'])->find($id);

        if (! $pengajuan) {
            throw new NotFoundHttpException('Pengajuan surat tidak ditemukan');
        }

        $this->authorize('verify', $pengajuan);

        if ($pengajuan->isFinal()) {
            throw new ConflictHttpException(
                'Pengajuan ini sudah berstatus final ('.$pengajuan->current_status->value.') dan tidak dapat diproses ulang'
            );
        }

        $user = $request->user();

        // Routing ke Service method yang sesuai berdasarkan role
        if ($user->hasRole(RoleName::KETUA_RT->value)) {
            $pengajuan = $this->suratService->reviewByRt($user, $pengajuan, $request->validated());
        } else {
            $pengajuan = $this->suratService->verifyByRw($user, $pengajuan, $request->validated());
        }

        return response()->json([
            'success' => true,
            'message' => 'Status pengajuan berhasil diperbarui',
            'data' => [
                'tracking_code' => $pengajuan->tracking_code,
                'current_status' => $pengajuan->current_status?->value,
                'nomor_surat' => $pengajuan->nomor_surat,
            ],
        ], Response::HTTP_OK);
    }
}
