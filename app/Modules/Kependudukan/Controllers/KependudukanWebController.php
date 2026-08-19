<?php

declare(strict_types=1);

namespace App\Modules\Kependudukan\Controllers;

use App\Http\Controllers\Controller;
use App\Models\KartuKeluarga;
use App\Models\Warga;
use App\Modules\Kependudukan\Requests\ListKartuKeluargaRequest;
use App\Modules\Kependudukan\Requests\ListWargaRequest;
use App\Modules\Kependudukan\Requests\StoreKartuKeluargaRequest;
use App\Modules\Kependudukan\Requests\StoreWargaRequest;
use App\Modules\Kependudukan\Requests\UpdateWargaRequest;
use App\Modules\Kependudukan\Requests\VerifyWargaRequest;
use App\Modules\Kependudukan\Services\KartuKeluargaService;
use App\Modules\Kependudukan\Services\WargaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class KependudukanWebController extends Controller
{
    public function __construct(
        protected WargaService $wargaService,
        protected KartuKeluargaService $kartuKeluargaService
    ) {}

    /**
     * Menampilkan daftar warga (HTML Blade View).
     * Policy: WargaPolicy::viewAny()
     */
    public function indexWarga(ListWargaRequest $request): View
    {
        $this->authorize('viewAny', Warga::class);

        $user = $request->user();
        $wargas = $this->wargaService->listWarga($user, $request->validated());

        return view('kependudukan.warga.index', compact('wargas'));
    }

    /**
     * Menampilkan formulir pendaftaran warga baru.
     * Policy: WargaPolicy::create()
     */
    public function createWarga(): View
    {
        $this->authorize('create', Warga::class);

        return view('kependudukan.warga.create');
    }

    /**
     * Menyimpan data warga baru ke sistem.
     * Policy: WargaPolicy::create()
     */
    public function storeWarga(StoreWargaRequest $request): RedirectResponse
    {
        $this->authorize('create', Warga::class);

        try {
            $warga = $this->wargaService->createWarga(
                $request->user(),
                $request->validated(),
                $request->ip()
            );

            return redirect()
                ->route('kependudukan.warga.show', ['nik_hash' => $warga->nik_hash])
                ->with('success', 'Data warga berhasil ditambahkan dan berstatus Menunggu Verifikasi');
        } catch (ConflictHttpException $e) {
            return back()
                ->withInput()
                ->withErrors(['nik' => $e->getMessage()]);
        }
    }

    /**
     * Menampilkan detail data warga.
     * Policy: WargaPolicy::view($warga)
     */
    public function showWarga(Request $request, string $nik_hash): View
    {
        $warga = Warga::with(['kartuKeluarga', 'verifiedBy'])->where('nik_hash', $nik_hash)->first();

        if (! $warga) {
            throw new NotFoundHttpException('Data warga tidak ditemukan');
        }

        $this->authorize('view', $warga);

        $this->wargaService->logDetailView($request->user(), $warga, $request->ip());

        return view('kependudukan.warga.show', compact('warga'));
    }

    /**
     * Menampilkan formulir edit data warga.
     * Policy: WargaPolicy::update($warga)
     */
    public function editWarga(Request $request, string $nik_hash): View
    {
        $warga = Warga::with('kartuKeluarga')->where('nik_hash', $nik_hash)->first();

        if (! $warga) {
            throw new NotFoundHttpException('Data warga tidak ditemukan');
        }

        $this->authorize('update', $warga);

        return view('kependudukan.warga.edit', compact('warga'));
    }

    /**
     * Menyimpan pembaruan data warga.
     * Policy: WargaPolicy::update($warga)
     */
    public function updateWarga(UpdateWargaRequest $request, string $nik_hash): RedirectResponse
    {
        $warga = Warga::with('kartuKeluarga')->where('nik_hash', $nik_hash)->first();

        if (! $warga) {
            throw new NotFoundHttpException('Data warga tidak ditemukan');
        }

        $this->authorize('update', $warga);

        $this->wargaService->updateWarga(
            $request->user(),
            $warga,
            $request->validated()
        );

        return redirect()
            ->route('kependudukan.warga.show', ['nik_hash' => $warga->nik_hash])
            ->with('success', 'Data warga berhasil diperbarui dan menunggu verifikasi ulang');
    }

    /**
     * Menampilkan formulir verifikasi warga (khusus Sekretaris RW).
     * Policy: WargaPolicy::verify($warga)
     */
    public function verifyWargaForm(Request $request, string $nik_hash): View
    {
        $warga = Warga::with('kartuKeluarga')->where('nik_hash', $nik_hash)->first();

        if (! $warga) {
            throw new NotFoundHttpException('Data warga tidak ditemukan');
        }

        $this->authorize('verify', $warga);

        return view('kependudukan.warga.verify', compact('warga'));
    }

    /**
     * Memproses keputusan verifikasi warga.
     * Policy: WargaPolicy::verify($warga)
     */
    public function verifyWarga(VerifyWargaRequest $request, string $nik_hash): RedirectResponse
    {
        $warga = Warga::with('kartuKeluarga')->where('nik_hash', $nik_hash)->first();

        if (! $warga) {
            throw new NotFoundHttpException('Data warga tidak ditemukan');
        }

        $this->authorize('verify', $warga);

        try {
            $this->wargaService->verifyWarga(
                $request->user(),
                $warga,
                $request->validated()
            );

            return redirect()
                ->route('kependudukan.warga.show', ['nik_hash' => $warga->nik_hash])
                ->with('success', 'Data warga berhasil diverifikasi');
        } catch (ConflictHttpException $e) {
            return redirect()
                ->route('kependudukan.warga.show', ['nik_hash' => $warga->nik_hash])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Menampilkan daftar Kartu Keluarga (HTML Blade View).
     * Policy: KartuKeluargaPolicy::viewAny()
     */
    public function indexKK(ListKartuKeluargaRequest $request): View
    {
        $this->authorize('viewAny', KartuKeluarga::class);

        $user = $request->user();
        $kartuKeluargas = $this->kartuKeluargaService->listKartuKeluarga($user, $request->validated());

        return view('kependudukan.kk.index', compact('kartuKeluargas'));
    }

    /**
     * Menampilkan formulir pendaftaran Kartu Keluarga baru.
     * Policy: KartuKeluargaPolicy::create()
     */
    public function createKK(): View
    {
        $this->authorize('create', KartuKeluarga::class);

        return view('kependudukan.kk.create');
    }

    /**
     * Menyimpan data Kartu Keluarga baru.
     * Policy: KartuKeluargaPolicy::create()
     */
    public function storeKK(StoreKartuKeluargaRequest $request): RedirectResponse
    {
        $this->authorize('create', KartuKeluarga::class);

        try {
            $this->kartuKeluargaService->createKartuKeluarga(
                $request->user(),
                $request->validated(),
                $request->ip()
            );

            return redirect()
                ->route('kependudukan.kk.index')
                ->with('success', 'Data Kartu Keluarga berhasil ditambahkan');
        } catch (ConflictHttpException $e) {
            return back()
                ->withInput()
                ->withErrors(['no_kk' => $e->getMessage()]);
        }
    }
}
