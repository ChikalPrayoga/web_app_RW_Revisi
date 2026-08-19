<?php

declare(strict_types=1);

namespace App\Modules\LaporanAspirasi\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LaporanAspirasi;
use App\Modules\LaporanAspirasi\Requests\UpdateStatusLaporanRequest;
use App\Modules\LaporanAspirasi\Services\LaporanAspirasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class LaporanAspirasiWebController extends Controller
{
    public function __construct(
        private readonly LaporanAspirasiService $service
    ) {}

    /**
     * GET /laporan-aspirasi — daftar laporan untuk pengurus.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LaporanAspirasi::class);

        $filters = $request->only(['current_status', 'sort_by', 'per_page']);
        $laporan = $this->service->listLaporan($filters);

        return view('laporan-aspirasi.index', compact('laporan', 'filters'));
    }

    /**
     * GET /laporan-aspirasi/{id} — detail laporan untuk pengurus.
     */
    public function show(int $id): View
    {
        $laporan = LaporanAspirasi::findOrFail($id);

        $this->authorize('view', $laporan);

        return view('laporan-aspirasi.show', compact('laporan'));
    }

    /**
     * POST /laporan-aspirasi/{id}/status — update status laporan oleh pengurus.
     */
    public function updateStatus(UpdateStatusLaporanRequest $request, int $id): RedirectResponse
    {
        $laporan = LaporanAspirasi::findOrFail($id);

        $this->authorize('updateStatus', $laporan);

        try {
            $this->service->updateStatus($laporan, $request->user(), $request->validated());
        } catch (UnprocessableEntityHttpException $e) {
            return redirect()
                ->route('laporan-aspirasi.show', $id)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('laporan-aspirasi.show', $id)
            ->with('success', 'Status laporan berhasil diperbarui.');
    }
}
