"""
keuangan_tools.py
SIM Layanan Warga RW 047 — Handler: Modul Keuangan
Versi: 1.0
Referensi: docs/API_SPECIFICATION.md §3.6 | .agents/AGENTS.md §2.1, §2.3, §3.1, §3.3

Mengimplementasikan tool handler untuk endpoint Keuangan:
  - list_iuran_types       → GET   /iuran-types
  - create_catatan_iuran   → POST  /catatan-iuran
  - approve_catatan_iuran  → PATCH /catatan-iuran/{id}/approve  ← WAJIB human_confirmed=True
  - get_rekapitulasi_iuran → GET   /catatan-iuran/rekapitulasi

ATURAN KRITIS:
  - approve_catatan_iuran WAJIB menolak eksekusi (PermissionError) jika
    human_confirmed tidak eksplisit True (AGENTS.md §3.1) — transaksi keuangan.
  - create_catatan_iuran WAJIB menangani 409 Conflict (duplikasi periode)
    dan TIDAK melakukan retry otomatis — cek via GET sebelum retry (AGENTS.md §2.3).
  - No. KK DILARANG di-log dalam bentuk unmasked (AGENTS.md §3.2, RULES.md §2.3).

Dependensi runtime: httpx — konfirmasikan ketersediaan ke pengguna (RULES.md §2.2).
"""

from __future__ import annotations

import os
import re
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


def _validate_no_kk(no_kk: str) -> None:
    """Validasi No. KK: wajib 16 digit angka sebelum pemanggilan tool (AGENTS.md §2.1)."""
    if not re.fullmatch(r"\d{16}", no_kk):
        raise ValueError("No. KK harus terdiri dari tepat 16 digit angka.")


def _build_headers(access_token: str) -> dict[str, str]:
    """Bangun HTTP header standar dengan Bearer token."""
    return {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Authorization": f"Bearer {access_token}",
    }


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

    if response.status_code == 409:
        # Duplikasi iuran periode — AGENTS.md §2.1: wajib ditangani, jangan retry otomatis
        raise DuplicateIuranError(
            body.get("message", "Duplikasi pencatatan iuran untuk periode yang sama"),
            body=body,
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
    Tidak retry pada 4xx (termasuk 409 Conflict).
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
# TOOL: list_iuran_types → GET /iuran-types
# ─────────────────────────────────────────────────────────────────────────────
def list_iuran_types(access_token: str) -> dict[str, Any]:
    """
    Mengambil daftar jenis iuran yang aktif.
    Endpoint: GET /iuran-types (API_SPECIFICATION.md §3.6.1)
    Akses: Seluruh peran terautentikasi.

    Digunakan sebagai referensi sebelum create_catatan_iuran untuk mendapatkan
    iuran_type_id yang valid.

    Args:
        access_token: Bearer token.

    Returns:
        dict berisi list jenis iuran: id, name, code, default_amount, is_active.
    """
    url: str = f"{_BASE_URL}/iuran-types"
    headers: dict[str, str] = _build_headers(access_token)

    return _request_with_retry("GET", url, headers)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: create_catatan_iuran → POST /catatan-iuran
# ─────────────────────────────────────────────────────────────────────────────
def create_catatan_iuran(
    access_token: str,
    no_kk: str,
    iuran_type_id: int,
    nominal: float,
    periode_bulan: int,
    periode_tahun: int,
    tanggal_pembayaran: str,
) -> dict[str, Any]:
    """
    Mencatat transaksi pembayaran iuran warga oleh Ketua RT.
    Endpoint: POST /catatan-iuran (API_SPECIFICATION.md §3.6.2)
    Akses: KETUA_RT.

    No. KK DILARANG di-log (AGENTS.md §3.2). Validasi format no_kk dilakukan
    sebelum pemanggilan API (AGENTS.md §2.1).

    PENANGANAN 409 CONFLICT (AGENTS.md §2.1, §2.3):
    Jika kombinasi no_kk + iuran_type_id + periode sudah tercatat (UNIQUE constraint,
    DATABASE_SCHEMA.md §3.10), server mengembalikan 409 Conflict. Agent WAJIB
    menangani ini sebagai DuplicateIuranError dan TIDAK melakukan retry.
    Cek data via list_catatan_iuran (jika tersedia) sebelum retry POST.

    Args:
        access_token:       Bearer token milik KETUA_RT.
        no_kk:             Nomor Kartu Keluarga (16 digit) — tidak di-log.
        iuran_type_id:     ID jenis iuran (peroleh dari list_iuran_types).
        nominal:           Nominal pembayaran dalam Rupiah.
        periode_bulan:     Bulan periode pembayaran (1–12).
        periode_tahun:     Tahun periode pembayaran.
        tanggal_pembayaran: Tanggal pembayaran (format YYYY-MM-DD).

    Returns:
        dict berisi iuran_id, no_kk_masked, nominal, periode, status = PENDING.

    Raises:
        ValueError:         Jika no_kk tidak 16 digit (pra-validasi).
        DuplicateIuranError: Jika 409 — iuran periode yang sama sudah ada (jangan retry!).
    """
    _validate_no_kk(no_kk)

    if not (1 <= periode_bulan <= 12):
        raise ValueError(f"periode_bulan harus antara 1–12. Diterima: {periode_bulan}")

    url: str = f"{_BASE_URL}/catatan-iuran"
    headers: dict[str, str] = _build_headers(access_token)
    payload: dict[str, Any] = {
        "no_kk": no_kk,
        "iuran_type_id": iuran_type_id,
        "nominal": nominal,
        "periode_bulan": periode_bulan,
        "periode_tahun": periode_tahun,
        "tanggal_pembayaran": tanggal_pembayaran,
    }

    return _request_with_retry("POST", url, headers, json_body=payload)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: approve_catatan_iuran → PATCH /catatan-iuran/{id}/approve
# WAJIB KONFIRMASI MANUSIA (AGENTS.md §3.1)
# ─────────────────────────────────────────────────────────────────────────────
def approve_catatan_iuran(
    access_token: str,
    iuran_id: int,
    action: str,
    rejection_notes: str | None = None,
    human_confirmed: bool = False,
) -> dict[str, Any]:
    """
    Menyetujui atau menolak transaksi iuran yang dicatat Ketua RT.
    Endpoint: PATCH /catatan-iuran/{id}/approve (API_SPECIFICATION.md §3.6.3)
    Akses: BENDAHARA_RW.

    ⚠️  AKSI INI WAJIB MELALUI KONFIRMASI MANUSIA (AGENTS.md §3.1).
    Fungsi ini MENOLAK eksekusi (PermissionError) jika human_confirmed tidak
    eksplisit True — transaksi keuangan yang sudah disetujui bersifat final.
    Pola ini konsisten di seluruh handler yang memerlukan konfirmasi manusia.

    Args:
        access_token:     Bearer token milik BENDAHARA_RW.
        iuran_id:         ID catatan iuran yang akan di-approve/reject.
        action:           "APPROVE" atau "REJECT".
        rejection_notes:  Wajib diisi jika action == "REJECT".
        human_confirmed:  WAJIB True — harus dikonfirmasi manusia secara eksplisit
                          sebelum memanggil fungsi ini (AGENTS.md §3.1).

    Returns:
        dict berisi iuran_id, status baru (APPROVED/REJECTED), approved_by, approved_at.

    Raises:
        PermissionError: Jika human_confirmed bukan True (guardrail human-in-the-loop).
        ValueError:      Jika action tidak valid atau REJECT tanpa rejection_notes.
        ClientError:     403 jika caller bukan BENDAHARA_RW, atau 422 jika validasi gagal.
    """
    # ── GUARDRAIL: human-in-the-loop wajib untuk transaksi keuangan (AGENTS.md §3.1) ──
    if human_confirmed is not True:
        raise PermissionError(
            "approve_catatan_iuran memerlukan konfirmasi eksplisit manusia. "
            "Setel human_confirmed=True setelah mendapatkan persetujuan pengguna "
            "sebelum memanggil fungsi ini. Transaksi keuangan bersifat final "
            "setelah disetujui. (AGENTS.md §3.1)"
        )
    # ─────────────────────────────────────────────────────────────────────────

    valid_actions = {"APPROVE", "REJECT"}
    if action not in valid_actions:
        raise ValueError(f"action harus salah satu dari: {valid_actions}. Diterima: {action!r}")

    if action == "REJECT" and not rejection_notes:
        raise ValueError(
            "rejection_notes wajib diisi ketika action adalah REJECT "
            "(API_SPECIFICATION.md §3.6.3)."
        )

    url: str = f"{_BASE_URL}/catatan-iuran/{iuran_id}/approve"
    headers: dict[str, str] = _build_headers(access_token)
    payload: dict[str, Any] = {"action": action}
    if rejection_notes is not None:
        payload["rejection_notes"] = rejection_notes

    return _request_with_retry("PATCH", url, headers, json_body=payload)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: get_rekapitulasi_iuran → GET /catatan-iuran/rekapitulasi
# ─────────────────────────────────────────────────────────────────────────────
def get_rekapitulasi_iuran(
    access_token: str,
    periode_bulan: int,
    periode_tahun: int,
    rt_code: str | None = None,
) -> dict[str, Any]:
    """
    Mengambil rekapitulasi laporan keuangan iuran per periode/RT.
    Endpoint: GET /catatan-iuran/rekapitulasi (API_SPECIFICATION.md §3.6.4)
    Akses: BENDAHARA_RW, KETUA_RW, SUPER_ADMIN.

    periode_bulan dan periode_tahun bersifat WAJIB sesuai API_SPECIFICATION.md §3.6.4.
    Validasi dilakukan sebelum pemanggilan API (AGENTS.md §2.1).

    Args:
        access_token:  Bearer token.
        periode_bulan: Bulan periode (1–12) — WAJIB.
        periode_tahun: Tahun periode — WAJIB.
        rt_code:       Filter wilayah tertentu (kosongkan untuk seluruh RW).

    Returns:
        dict berisi periode, total KK wajib bayar, KK sudah bayar, total nominal,
        dan rincian per jenis iuran.

    Raises:
        ValueError: Jika periode_bulan di luar rentang 1–12.
    """
    if not (1 <= periode_bulan <= 12):
        raise ValueError(f"periode_bulan harus antara 1–12. Diterima: {periode_bulan}")

    url: str = f"{_BASE_URL}/catatan-iuran/rekapitulasi"
    headers: dict[str, str] = _build_headers(access_token)

    params: dict[str, Any] = {
        "periode_bulan": periode_bulan,
        "periode_tahun": periode_tahun,
    }
    if rt_code is not None:
        params["rt_code"] = rt_code

    return _request_with_retry("GET", url, headers, params=params)


# ─────────────────────────────────────────────────────────────────────────────
# EXCEPTION CLASSES (RULES.md §3.1)
# ─────────────────────────────────────────────────────────────────────────────
class KeuanganToolError(Exception):
    """Base exception untuk seluruh keuangan tool handler."""


class RateLimitError(KeuanganToolError):
    """429 — caller wajib menghormati retry_after_seconds."""

    def __init__(self, message: str, retry_after_seconds: int = 60) -> None:
        super().__init__(message)
        self.retry_after_seconds: int = retry_after_seconds


class DuplicateIuranError(KeuanganToolError):
    """
    409 Conflict — iuran untuk kombinasi no_kk + iuran_type_id + periode sudah ada.
    JANGAN RETRY — cek data terlebih dahulu via endpoint lain (AGENTS.md §2.1, §2.3).
    """

    def __init__(self, message: str, body: dict[str, Any] | None = None) -> None:
        super().__init__(message)
        self.body: dict[str, Any] = body or {}


class ValidationError(KeuanganToolError):
    """422 Unprocessable Entity — detail kesalahan per field tersedia di errors."""

    def __init__(self, message: str, errors: dict[str, Any] | None = None) -> None:
        super().__init__(message)
        self.errors: dict[str, Any] = errors or {}


class ClientError(KeuanganToolError):
    """4xx (bukan 409/429) — tidak di-retry; analisis dan laporkan."""

    def __init__(
        self,
        message: str,
        status_code: int = 400,
        body: dict[str, Any] | None = None,
    ) -> None:
        super().__init__(message)
        self.status_code: int = status_code
        self.body: dict[str, Any] = body or {}


class ServerError(KeuanganToolError):
    """5xx atau koneksi gagal setelah retry habis — laporkan ke Orchestrator."""

    def __init__(self, message: str, status_code: int | None = None) -> None:
        super().__init__(message)
        self.status_code: int | None = status_code
