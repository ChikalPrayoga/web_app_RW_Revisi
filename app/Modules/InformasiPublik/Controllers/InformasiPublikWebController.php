<?php

declare(strict_types=1);

namespace App\Modules\InformasiPublik\Controllers;

use App\Enums\KategoriInformasi;
use App\Enums\StatusInformasi;
use App\Http\Controllers\Controller;
use App\Models\InformasiPublik;
use App\Modules\InformasiPublik\Requests\ListInformasiPublikRequest;
use App\Modules\InformasiPublik\Requests\StoreInformasiPublikRequest;
use App\Modules\InformasiPublik\Requests\UpdateInformasiPublikRequest;
use App\Modules\InformasiPublik\Services\InformasiPublikService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Controller Blade Web untuk Manajemen Informasi Publik oleh Pengurus RW.
 */
class InformasiPublikWebController extends Controller
{
    public function __construct(
        protected InformasiPublikService $informasiService
    ) {}

    /**
     * Menampilkan daftar informasi publik untuk pengurus.
     */
    public function index(ListInformasiPublikRequest $request): View
    {
        $this->authorize('viewAny', InformasiPublik::class);

        $user = $request->user();
        $informasiList = $this->informasiService->listPengurus($user, $request->validated());

        return view('informasi-publik.index', [
            'informasiList' => $informasiList,
            'kategoris' => KategoriInformasi::cases(),
            'statuses' => StatusInformasi::cases(),
        ]);
    }

    /**
     * Menampilkan formulir tambah informasi publik baru.
     */
    public function create(): View
    {
        $this->authorize('create', InformasiPublik::class);

        return view('informasi-publik.create', [
            'kategoris' => KategoriInformasi::cases(),
            'statuses' => StatusInformasi::cases(),
        ]);
    }

    /**
     * Menyimpan data informasi publik baru.
     */
    public function store(StoreInformasiPublikRequest $request): RedirectResponse
    {
        $this->authorize('create', InformasiPublik::class);

        $user = $request->user();
        $this->informasiService->create($user, $request->validated(), $request->ip());

        return redirect()
            ->route('informasi-publik.index')
            ->with('success', 'Informasi publik berhasil disimpan.');
    }

    /**
     * Menampilkan formulir edit informasi publik.
     */
    public function edit(int $id): View
    {
        $informasi = $this->informasiService->getItemForPengurus($id);
        $this->authorize('update', $informasi);

        return view('informasi-publik.edit', [
            'informasi' => $informasi,
            'kategoris' => KategoriInformasi::cases(),
            'statuses' => StatusInformasi::cases(),
        ]);
    }

    /**
     * Menyimpan perubahan data informasi publik.
     */
    public function update(UpdateInformasiPublikRequest $request, int $id): RedirectResponse
    {
        $informasi = $this->informasiService->getItemForPengurus($id);
        $this->authorize('update', $informasi);

        $user = $request->user();
        $this->informasiService->update($user, $informasi, $request->validated(), $request->ip());

        return redirect()
            ->route('informasi-publik.index')
            ->with('success', 'Informasi publik berhasil diperbarui.');
    }

    /**
     * Menghapus informasi publik (soft delete).
     */
    public function destroy(int $id): RedirectResponse
    {
        $informasi = $this->informasiService->getItemForPengurus($id);
        $this->authorize('delete', $informasi);

        $user = request()->user();
        $this->informasiService->delete($user, $informasi, request()->ip());

        return redirect()
            ->route('informasi-publik.index')
            ->with('success', 'Informasi publik berhasil dihapus.');
    }
}
