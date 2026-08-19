<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Warga;
use App\Modules\Kependudukan\Requests\ListWargaRequest;
use App\Modules\Kependudukan\Requests\StoreWargaRequest;
use App\Modules\Kependudukan\Requests\UpdateWargaRequest;
use App\Modules\Kependudukan\Requests\VerifyWargaRequest;
use App\Modules\Kependudukan\Resources\WargaDetailResource;
use App\Modules\Kependudukan\Resources\WargaResource;
use App\Modules\Kependudukan\Services\WargaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WargaController extends Controller
{
    public function __construct(
        protected WargaService $wargaService
    ) {}

    /**
     * GET /api/v1/warga
     *
     * Policy: WargaPolicy::viewAny()
     * Area scoping untuk KETUA_RT dilakukan di WargaService::listWarga().
     */
    public function index(ListWargaRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Warga::class);

        $user = $request->user();

        $paginator = $this->wargaService->listWarga($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Daftar warga berhasil diambil',
            'data' => WargaResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * POST /api/v1/warga
     *
     * Policy: WargaPolicy::create()
     * Area scoping untuk KETUA_RT (validasi KK milik RT yang benar)
     * dilakukan di WargaService::createWarga() setelah KK di-resolve.
     */
    public function store(StoreWargaRequest $request): JsonResponse
    {
        $this->authorize('create', Warga::class);

        $user = $request->user();

        $warga = $this->wargaService->createWarga(
            $user,
            $request->validated(),
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'Data warga berhasil ditambahkan, menunggu verifikasi Sekretaris RW',
            'data' => [
                'nik_masked' => $warga->nik_masked,
                'nama_lengkap' => $warga->nama_lengkap,
                'verification_status' => $warga->verification_status,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/warga/{nik_hash}
     *
     * Policy: WargaPolicy::view($warga)
     * Resolve model dulu agar Policy dapat memeriksa rt_code
     * untuk penegakan area scoping KETUA_RT.
     */
    public function show(Request $request, string $nik_hash): JsonResponse
    {
        $warga = Warga::with('kartuKeluarga')->where('nik_hash', $nik_hash)->first();

        if (! $warga) {
            throw new NotFoundHttpException('Data warga tidak ditemukan');
        }

        $this->authorize('view', $warga);

        $user = $request->user();

        // Audit Trail untuk akses data detail (read action — tidak dicatat Observer)
        $warga = $this->wargaService->logDetailView($user, $warga, $request->ip());

        return response()->json([
            'success' => true,
            'message' => 'Data warga berhasil diambil',
            'data' => new WargaDetailResource($warga),
        ], Response::HTTP_OK);
    }

    /**
     * PATCH /api/v1/warga/{nik_hash}
     *
     * Policy: WargaPolicy::update($warga)
     * Resolve model dulu agar Policy dapat memeriksa rt_code.
     */
    public function update(UpdateWargaRequest $request, string $nik_hash): JsonResponse
    {
        $warga = Warga::with('kartuKeluarga')->where('nik_hash', $nik_hash)->first();

        if (! $warga) {
            throw new NotFoundHttpException('Data warga tidak ditemukan');
        }

        $this->authorize('update', $warga);

        $user = $request->user();

        $warga = $this->wargaService->updateWarga(
            $user,
            $warga,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Data warga berhasil diperbarui, menunggu verifikasi Sekretaris RW',
            'data' => [
                'nik_masked' => $warga->nik_masked,
                'verification_status' => $warga->verification_status,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * PATCH /api/v1/warga/{nik_hash}/verify
     *
     * Policy: WargaPolicy::verify($warga)
     */
    public function verify(VerifyWargaRequest $request, string $nik_hash): JsonResponse
    {
        $warga = Warga::where('nik_hash', $nik_hash)->first();

        if (! $warga) {
            throw new NotFoundHttpException('Data warga tidak ditemukan');
        }

        $this->authorize('verify', $warga);

        $user = $request->user();

        $warga = $this->wargaService->verifyWarga(
            $user,
            $warga,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Data warga berhasil diverifikasi',
            'data' => [
                'nik_masked' => $warga->nik_masked,
                'status_warga' => $warga->status_warga,
                'verification_status' => $warga->verification_status,
            ],
        ], Response::HTTP_OK);
    }
}
