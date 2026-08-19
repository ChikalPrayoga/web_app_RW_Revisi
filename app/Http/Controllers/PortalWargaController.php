<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\KategoriInformasi;
use App\Modules\InformasiPublik\Requests\ListInformasiPublikRequest;
use App\Modules\InformasiPublik\Services\InformasiPublikService;
use App\Modules\LaporanAspirasi\Requests\StoreLaporanAspirasiRequest;
use App\Modules\LaporanAspirasi\Services\LaporanAspirasiService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller untuk Portal Warga Publik (Self-Service, Informasi Publik, Laporan & Aspirasi).
 *
 * Portal Warga bertindak sebagai Composition Layer yang mengintegrasikan:
 * 1. Beranda Publik & Profil RW 047
 * 2. Katalog Informasi Publik (Pengumuman, Berita, Agenda)
 * 3. Akses Cepat Layanan Persuratan & Pelacakan Surat
 * 4. Layanan Pengaduan & Aspirasi Warga serta Pelacakan Tiket
 *
 * Seluruh akses pada portal ini tidak memerlukan autentikasi login.
 *
 * @see docs/PRD_SIM_Layanan_Warga_RW047.md §1.2
 * @see docs/UI_UX_SPECIFICATION.md §2.3
 */
class PortalWargaController extends Controller
{
    public function __construct(
        protected InformasiPublikService $informasiService,
        protected LaporanAspirasiService $laporanService
    ) {}

    /**
     * Beranda Utama Portal Warga RW 047.
     */
    public function index(): View
    {
        $latestInformasi = $this->informasiService->getLatestPublicContent(6);
        $upcomingAgendas = $this->informasiService->getUpcomingAgendas(4);
        $stats = $this->informasiService->getPublicStats();

        return view('portal.index', compact('latestInformasi', 'upcomingAgendas', 'stats'));
    }

    /**
     * Halaman Publik: Daftar Informasi, Pengumuman, Berita, dan Agenda.
     */
    public function informasiIndex(ListInformasiPublikRequest $request): View
    {
        $informasiList = $this->informasiService->listPublic($request->validated());
        $kategoris = KategoriInformasi::cases();
        $activeKategori = $request->input('kategori');
        $search = $request->input('search');

        return view('portal.informasi.index', compact('informasiList', 'kategoris', 'activeKategori', 'search'));
    }

    /**
     * Halaman Publik: Detail Informasi Publik.
     */
    public function informasiDetail(int $id): View
    {
        $informasi = $this->informasiService->getPublicItem($id);
        $relatedInformasi = $this->informasiService->getLatestPublicContent(3);

        return view('portal.informasi.show', compact('informasi', 'relatedInformasi'));
    }

    // =========================================================================
    // Modul: Laporan & Aspirasi Publik
    // =========================================================================

    /**
     * GET /laporan-aspirasi/ajukan — Tampilkan form pengaduan / aspirasi publik.
     */
    public function laporanCreate(): View
    {
        return view('portal.laporan.create');
    }

    /**
     * POST /laporan-aspirasi/ajukan — Simpan pengaduan / aspirasi dari warga.
     */
    public function laporanStore(StoreLaporanAspirasiRequest $request): RedirectResponse
    {
        $laporan = $this->laporanService->submitLaporan($request->validated());

        return redirect()
            ->route('portal.laporan.success', ['ticket_number' => $laporan->ticket_number])
            ->with('success', 'Laporan / Aspirasi berhasil dikirim!');
    }

    /**
     * GET /laporan-aspirasi/sukses/{ticket_number} — Halaman sukses setelah submit.
     */
    public function laporanSuccess(string $ticket_number): View
    {
        return view('portal.laporan.success', compact('ticket_number'));
    }

    /**
     * GET /laporan-aspirasi/lacak — Form input nomor tiket untuk pelacakan laporan.
     */
    public function laporanTrack(): View
    {
        return view('portal.laporan.track');
    }

    /**
     * GET /laporan-aspirasi/lacak/{ticket_number} — Hasil pelacakan status laporan.
     */
    public function laporanTrackResult(Request $request, ?string $ticket_number = null): View|RedirectResponse
    {
        $ticket = $ticket_number ?? $request->input('ticket_number');

        if (empty($ticket)) {
            return redirect()
                ->route('portal.laporan.track')
                ->with('error', 'Silakan masukkan nomor tiket laporan.');
        }

        try {
            $laporan = $this->laporanService->trackByTicket((string) $ticket);
        } catch (NotFoundHttpException) {
            return redirect()
                ->route('portal.laporan.track')
                ->with('error', "Nomor tiket '{$ticket}' tidak ditemukan. Pastikan nomor tiket sudah benar.");
        }

        return view('portal.laporan.track_result', compact('laporan'));
    }
}
