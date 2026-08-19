<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\KartuKeluarga;
use App\Modules\Kependudukan\Requests\ListKartuKeluargaRequest;
use App\Modules\Kependudukan\Requests\StoreKartuKeluargaRequest;
use App\Modules\Kependudukan\Resources\KartuKeluargaResource;
use App\Modules\Kependudukan\Services\KartuKeluargaService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class KartuKeluargaController extends Controller
{
    public function __construct(
        protected KartuKeluargaService $kartuKeluargaService
    ) {}

    /**
     * GET /api/v1/kartu-keluarga
     *
     * Policy: KartuKeluargaPolicy::viewAny()
     */
    public function index(ListKartuKeluargaRequest $request): JsonResponse
    {
        $this->authorize('viewAny', KartuKeluarga::class);

        $user = $request->user();

        $paginator = $this->kartuKeluargaService->listKartuKeluarga($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data Kartu Keluarga berhasil diambil',
            'data' => KartuKeluargaResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * POST /api/v1/kartu-keluarga
     *
     * Policy: KartuKeluargaPolicy::create()
     */
    public function store(StoreKartuKeluargaRequest $request): JsonResponse
    {
        $this->authorize('create', KartuKeluarga::class);

        $user = $request->user();

        $kk = $this->kartuKeluargaService->createKartuKeluarga(
            $user,
            $request->validated(),
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'Data Kartu Keluarga berhasil didaftarkan',
            'data' => [
                'id' => $kk->id,
                'no_kk_masked' => $kk->no_kk_masked,
                'rt_code' => $kk->rt_code,
                'status_kepemilikan_rumah' => $kk->status_kepemilikan_rumah,
                'created_at' => $kk->created_at?->toISOString(),
            ],
        ], Response::HTTP_CREATED);
    }
}
