"""
laporan_tools.py
SIM Layanan Warga RW 047 — Handler: Modul Laporan & Aspirasi
Versi: 1.0
Referensi: docs/API_SPECIFICATION.md §3.5 | .agents/AGENTS.md §2.1, §2.3, §3.1, §3.3

Mengimplementasikan tool handler untuk endpoint Laporan & Aspirasi:
  - submit_laporan_aspirasi  → POST  /laporan-aspirasi
  - track_laporan_aspirasi   → GET   /laporan-aspirasi/track/{ticket_number}
  - list_laporan_aspirasi    → GET   /laporan-aspirasi
  - update_laporan_status    → PATCH /laporan-aspirasi/{id}/status

ATURAN KRITIS:
  - update_laporan_status WAJIB menolak perubahan status CLOSED ke status apapun
    — bahkan jika diminta pengguna — dan melaporkan alasan penolakan (AGENTS.md §2.1).
  - Klasifikasi AI berjalan ASINKRON setelah POST /laporan-aspirasi;
    status klasifikasi dipantau via track_laporan_aspirasi() (SYSTEM_ARCHITECTURE.md §2.2).

Dependensi runtime: httpx — konfirmasikan ketersediaan ke pengguna (RULES.md §2.2).
"""

from __future__ import annotations

import os
import time
from typing import Any

import httpx

# ─────────────────────────────────────────────────────────────────────────────
# Konfigurasi dari environment variable (RULES.md §2.3)
# ─────────────────────────────────────────────────────────────────────────────
_BASE_URL: str = os.environ["API_BASE_URL"].rstrip("/")
_DEFAULT_TIMEOUT: float = float(os.environ.get("API_TIMEOUT_SECONDS", "30"))
_MAX_RETRY_5XX: int = int(os.environ.get("API_MAX_RETRIES", "3"))
_RETRY_BACKOFF_BASE: float = float(os.environ.get("API_RETRY_BACKOFF_BASE", "2.0"))

# Status yang bersifat FINAL — tidak boleh diubah kembali (AGENTS.md §2.1, §3.1)
_FINAL_STATUSES: frozenset[str] = frozenset({"CLOSED"})

# Urutan transisi status yang valid (berdasarkan alur pada PRD dan API_SPECIFICATION.md §3.5.4)
_VALID_STATUS_TRANSITIONS: dict[str, set[str]] = {
    "SUBMITTED":   {"CLASSIFIED", "IN_PROGRESS"},   # Setelah diterima atau diklasifikasi AI
    "CLASSIFIED":  {"IN_PROGRESS", "CLOSED"},
    "IN_PROGRESS": {"CLOSED"},
    "CLOSED":      set(),  # Final — tidak ada transisi valid dari CLOSED
}


def _build_headers(access_token: str | None = None) -> dict[str, str]:
    """Bangun HTTP header standar."""
    headers: dict[str, str] = {
        "Accept": "application/json",
        "Content-Type": "application/json",
    }
    if access_token is not None:
        headers["Authorization"] = f"Bearer {access_token}"
    return headers


def _handle_response(response: httpx.Response) -> dict[str, Any]:
    """
    Parsing response ke dict. Melempar exception bermakna per kode status
    (RULES.md §3.1, AGENTS.md §3.3).
    """
    try:
        body: dict[str, Any] = response.json()
    except Exception:
        body = {"success": False, "message": f"Respons bukan JSON valid (status {response.status_code})"}

    if response.status_code == 429:
        retry_after: int = int(response.headers.get("Retry-After", "60"))
        raise RateLimitError(
            f"Rate limit tercapai. Tunggu {retry_after} detik.",
            retry_after_seconds=retry_after,
        )

    if response.status_code == 422:
        raise ValidationError(
            body.get("message", "Validasi gagal"),
            errors=body.get("errors", {}),
        )

    if response.is_client_error:
        raise ClientError(
            body.get("message", f"Client error {response.status_code}"),
            status_code=response.status_code,
            body=body,
        )

    return body


def _request_with_retry(
    method: str,
    url: str,
    headers: dict[str, str],
    json_body: dict[str, Any] | None = None,
    params: dict[str, Any] | None = None,
) -> dict[str, Any]:
    """
    HTTP request dengan exponential backoff untuk 5xx, maks 3 kali (AGENTS.md §3.3).
    Tidak retry pada 4xx.
    """
    last_error: Exception | None = None

    for attempt in range(1, _MAX_RETRY_5XX + 1):
        try:
            with httpx.Client(timeout=_DEFAULT_TIMEOUT) as client:
                response: httpx.Response = client.request(
                    method=method,
                    url=url,
                    headers=headers,
                    json=json_body,
                    params=params,
                )

            if response.is_server_error and attempt < _MAX_RETRY_5XX:
                delay: float = _RETRY_BACKOFF_BASE ** attempt
                time.sleep(delay)
                last_error = ServerError(
                    f"Server error {response.status_code} pada percobaan {attempt}",
                    status_code=response.status_code,
                )
                continue

            return _handle_response(response)

        except (httpx.TimeoutException, httpx.ConnectError) as exc:
            if attempt < _MAX_RETRY_5XX:
                delay = _RETRY_BACKOFF_BASE ** attempt
                time.sleep(delay)
                last_error = exc
                continue
            raise ServerError(f"Koneksi gagal setelah {_MAX_RETRY_5XX} percobaan: {exc}") from exc

    raise ServerError(f"Server tidak merespons setelah {_MAX_RETRY_5XX} percobaan.") from last_error


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: submit_laporan_aspirasi → POST /laporan-aspirasi
# ─────────────────────────────────────────────────────────────────────────────
def submit_laporan_aspirasi(
    access_token: str,
    judul_laporan: str,
    teks_keluhan: str,
    lokasi_kejadian: str,
) -> dict[str, Any]:
    """
    Mengirim laporan/aspirasi baru dari warga.
    Endpoint: POST /laporan-aspirasi (API_SPECIFICATION.md §3.5.1)
    Akses: WARGA.

    Setelah tersimpan, sistem menjadwalkan ClassifyLaporanJob secara ASINKRON
    (SYSTEM_ARCHITECTURE.md §2.2). Hasil klasifikasi AI baru tersedia setelah
    job selesai dan dapat dipantau via track_laporan_aspirasi(). Status saat
    ini akan SUBMITTED → CLASSIFIED setelah job selesai.

    Jika layanan AI tidak merespons, job tetap berstatus SUBMITTED sampai batas
    retry tercapai; fallback ke klasifikasi manual (AGENTS.md §3.3).

    Args:
        access_token:    Bearer token milik warga.
        judul_laporan:   Judul singkat laporan.
        teks_keluhan:    Deskripsi keluhan (minimal 20 karakter — validasi backend).
        lokasi_kejadian: Lokasi kejadian yang dilaporkan.

    Returns:
        dict berisi aspirasi_id, ticket_number, current_status, submitted_at.
    """
    url: str = f"{_BASE_URL}/laporan-aspirasi"
    headers: dict[str, str] = _build_headers(access_token=access_token)
    payload: dict[str, str] = {
        "judul_laporan": judul_laporan,
        "teks_keluhan": teks_keluhan,
        "lokasi_kejadian": lokasi_kejadian,
    }

    return _request_with_retry("POST", url, headers, json_body=payload)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: track_laporan_aspirasi → GET /laporan-aspirasi/track/{ticket_number}
# ─────────────────────────────────────────────────────────────────────────────
def track_laporan_aspirasi(ticket_number: str) -> dict[str, Any]:
    """
    Melacak status penanganan laporan menggunakan nomor tiket.
    Endpoint: GET /laporan-aspirasi/track/{ticket_number} (API_SPECIFICATION.md §3.5.2)
    Akses: Publik — tidak memerlukan token.

    Dapat digunakan untuk polling status klasifikasi AI setelah submit
    (sesuai catatan SYSTEM_ARCHITECTURE.md §2.2 dan API_SPECIFICATION.md §5).

    Args:
        ticket_number: Nomor tiket laporan (mis. "LPR2026081200088").

    Returns:
        dict berisi ticket_number, judul_laporan, current_status, kategori_ai,
        submitted_at, resolved_at.

    Raises:
        ClientError: 404 jika ticket_number tidak ditemukan.
    """
    url: str = f"{_BASE_URL}/laporan-aspirasi/track/{ticket_number}"
    headers: dict[str, str] = _build_headers()  # Tidak perlu token

    return _request_with_retry("GET", url, headers)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: list_laporan_aspirasi → GET /laporan-aspirasi
# ─────────────────────────────────────────────────────────────────────────────
def list_laporan_aspirasi(
    access_token: str,
    current_status: str | None = None,
    kategori_ai: str | None = None,
    sort_by: str = "submitted_at",
    sort_dir: str = "desc",
    page: int = 1,
    per_page: int = 15,
) -> dict[str, Any]:
    """
    Mengambil daftar laporan/aspirasi untuk pemantauan pengurus.
    Endpoint: GET /laporan-aspirasi (API_SPECIFICATION.md §3.5.3)
    Akses: KETUA_RT, SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.

    Mendukung filter berdasarkan kategori hasil klasifikasi AI
    (diisi backend setelah ClassifyLaporanJob selesai).

    Args:
        access_token:   Bearer token.
        current_status: Filter status penanganan.
        kategori_ai:    Filter berdasarkan kategori klasifikasi AI (mis. "Infrastruktur").
        sort_by:        "submitted_at" (default) atau "skor_prioritas_ai".
        sort_dir:       "asc" atau "desc".
        page:           Halaman pagination.
        per_page:       Item per halaman.

    Returns:
        dict berisi list laporan dan metadata pagination.
    """
    url: str = f"{_BASE_URL}/laporan-aspirasi"
    headers: dict[str, str] = _build_headers(access_token=access_token)

    params: dict[str, Any] = {
        "sort_by": sort_by,
        "sort_dir": sort_dir,
        "page": page,
        "per_page": per_page,
    }
    if current_status is not None:
        params["current_status"] = current_status
    if kategori_ai is not None:
        params["kategori_ai"] = kategori_ai

    return _request_with_retry("GET", url, headers, params=params)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: update_laporan_status → PATCH /laporan-aspirasi/{id}/status
# ─────────────────────────────────────────────────────────────────────────────
def update_laporan_status(
    access_token: str,
    aspirasi_id: int,
    current_status: str,
    catatan_tindak_lanjut: str | None = None,
    current_status_from_db: str | None = None,
) -> dict[str, Any]:
    """
    Memperbarui status penanganan laporan oleh pengurus RW.
    Endpoint: PATCH /laporan-aspirasi/{id}/status (API_SPECIFICATION.md §3.5.4)
    Akses: KETUA_RT, SEKRETARIS_RW, KETUA_RW.

    ⚠️  LARANGAN KERAS (AGENTS.md §2.1):
    Agent WAJIB menolak permintaan mengubah status CLOSED ke status apapun,
    bahkan jika diminta pengguna secara eksplisit. Penolakan harus disertai
    alasan yang jelas. Fungsi ini menegakkan aturan ini sebagai guardrail lokal
    sebelum pemanggilan API.

    Args:
        access_token:          Bearer token.
        aspirasi_id:           ID laporan yang statusnya diperbarui.
        current_status:        Status baru yang dituju (mis. "IN_PROGRESS", "CLOSED").
        catatan_tindak_lanjut: Catatan tindak lanjut dari pengurus.
        current_status_from_db: Status saat ini dari database (opsional, untuk validasi
                                transisi lokal sebelum pemanggilan API). Jika disediakan,
                                fungsi akan memvalidasi apakah transisi diizinkan.

    Returns:
        dict berisi aspirasi_id, current_status baru, updated_by, updated_at.

    Raises:
        ClosedStatusError: Jika current_status_from_db adalah "CLOSED" — perubahan
                           dari CLOSED ditolak secara mutlak (AGENTS.md §2.1, §3.1).
        InvalidTransitionError: Jika transisi status tidak sesuai alur yang diizinkan.
        ClientError:       422 jika transisi ditolak backend.
    """
    # ── GUARDRAIL: mencegah perubahan dari status CLOSED (AGENTS.md §2.1) ────
    if current_status_from_db is not None and current_status_from_db in _FINAL_STATUSES:
        raise ClosedStatusError(
            f"Laporan dengan ID {aspirasi_id} sudah berstatus CLOSED (final). "
            "Status CLOSED tidak dapat diubah kembali ke status manapun — "
            "ini adalah aturan bisnis mutlak (AGENTS.md §2.1). "
            "Permintaan ini ditolak meskipun diminta secara eksplisit."
        )

    # ── Validasi transisi lokal jika status sebelumnya diketahui ────────────
    if current_status_from_db is not None:
        allowed_next: set[str] = _VALID_STATUS_TRANSITIONS.get(current_status_from_db, set())
        if current_status not in allowed_next:
            raise InvalidTransitionError(
                f"Transisi status tidak valid: {current_status_from_db!r} → {current_status!r}. "
                f"Status yang diizinkan dari {current_status_from_db!r}: {allowed_next or '(tidak ada — status final)'}."
            )
    # ─────────────────────────────────────────────────────────────────────────

    url: str = f"{_BASE_URL}/laporan-aspirasi/{aspirasi_id}/status"
    headers: dict[str, str] = _build_headers(access_token=access_token)
    payload: dict[str, Any] = {"current_status": current_status}
    if catatan_tindak_lanjut is not None:
        payload["catatan_tindak_lanjut"] = catatan_tindak_lanjut

    return _request_with_retry("PATCH", url, headers, json_body=payload)


# ─────────────────────────────────────────────────────────────────────────────
# EXCEPTION CLASSES (RULES.md §3.1)
# ─────────────────────────────────────────────────────────────────────────────
class LaporanToolError(Exception):
    """Base exception untuk seluruh laporan tool handler."""


class RateLimitError(LaporanToolError):
    """429 — caller wajib menghormati retry_after_seconds."""

    def __init__(self, message: str, retry_after_seconds: int = 60) -> None:
        super().__init__(message)
        self.retry_after_seconds: int = retry_after_seconds


class ClientError(LaporanToolError):
    """4xx (bukan 429) — tidak di-retry; analisis dan laporkan ke Orchestrator."""

    def __init__(
        self,
        message: str,
        status_code: int = 400,
        body: dict[str, Any] | None = None,
    ) -> None:
        super().__init__(message)
        self.status_code: int = status_code
        self.body: dict[str, Any] = body or {}


class ValidationError(LaporanToolError):
    """422 Unprocessable Entity — detail kesalahan per field tersedia di errors."""

    def __init__(self, message: str, errors: dict[str, Any] | None = None) -> None:
        super().__init__(message)
        self.errors: dict[str, Any] = errors or {}


class ClosedStatusError(LaporanToolError):
    """
    Dilempar saat Agent mencoba mengubah status laporan yang sudah CLOSED.
    Mengimplementasikan guardrail dari AGENTS.md §2.1 dan §3.1.
    Alasan penolakan wajib disampaikan ke pengguna/Orchestrator.
    """


class InvalidTransitionError(LaporanToolError):
    """Dilempar saat transisi status tidak sesuai alur yang diizinkan."""


class ServerError(LaporanToolError):
    """5xx atau koneksi gagal setelah retry habis — laporkan ke Orchestrator."""

    def __init__(self, message: str, status_code: int | None = None) -> None:
        super().__init__(message)
        self.status_code: int | None = status_code
