"""
persuratan_tools.py
SIM Layanan Warga RW 047 — Handler: Modul Persuratan
Versi: 1.0
Referensi: docs/API_SPECIFICATION.md §3.4 | .agents/AGENTS.md §2.1, §2.3, §3.1, §3.3

Mengimplementasikan tool handler untuk endpoint Persuratan:
  - submit_pengajuan_surat  → POST /surat/pengajuan
  - track_pengajuan_surat   → GET  /surat/pengajuan/track/{tracking_code}
  - list_pengajuan_surat    → GET  /surat/pengajuan
  - verify_pengajuan_surat  → POST /surat/pengajuan/{id}/verify  ← WAJIB human_confirmed=True

ATURAN KRITIS:
  - verify_pengajuan_surat WAJIB menolak eksekusi (PermissionError) jika
    human_confirmed tidak eksplisit True (AGENTS.md §3.1).
  - Area scoping: list_pengajuan_surat WAJIB menghormati filter RT;
    Agent tidak boleh membangun query yang melewati filter wilayah (AGENTS.md §2.1).

Dependensi runtime: httpx — konfirmasikan ketersediaan ke pengguna (RULES.md §2.2).
"""

from __future__ import annotations

import os
import time
import uuid
from typing import Any

import httpx

# ─────────────────────────────────────────────────────────────────────────────
# Konfigurasi dari environment variable (RULES.md §2.3)
# ─────────────────────────────────────────────────────────────────────────────
_BASE_URL: str = os.environ["API_BASE_URL"].rstrip("/")
_DEFAULT_TIMEOUT: float = float(os.environ.get("API_TIMEOUT_SECONDS", "30"))
_MAX_RETRY_5XX: int = int(os.environ.get("API_MAX_RETRIES", "3"))
_RETRY_BACKOFF_BASE: float = float(os.environ.get("API_RETRY_BACKOFF_BASE", "2.0"))


def _build_headers(
    access_token: str | None = None,
    idempotency_key: str | None = None,
) -> dict[str, str]:
    """
    Bangun HTTP header standar.
    Idempotency-Key disertakan untuk endpoint aksi kritikal sesuai
    API_SPECIFICATION.md §1.8 (verify_pengajuan_surat).
    """
    headers: dict[str, str] = {
        "Accept": "application/json",
        "Content-Type": "application/json",
    }
    if access_token is not None:
        headers["Authorization"] = f"Bearer {access_token}"
    if idempotency_key is not None:
        headers["Idempotency-Key"] = idempotency_key
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

    if response.status_code == 409:
        raise ConflictError(body.get("message", "Konflik status pengajuan"), body=body)

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
# TOOL: submit_pengajuan_surat → POST /surat/pengajuan
# ─────────────────────────────────────────────────────────────────────────────
def submit_pengajuan_surat(
    access_token: str,
    jenis_surat: str,
    keperluan: str,
) -> dict[str, Any]:
    """
    Mengajukan permohonan surat baru oleh warga.
    Endpoint: POST /surat/pengajuan (API_SPECIFICATION.md §3.4.1)
    Akses: WARGA.

    Setelah berhasil, response berisi tracking_code yang dapat digunakan
    oleh warga untuk memantau status via track_pengajuan_surat() tanpa login.

    Args:
        access_token: Bearer token milik warga.
        jenis_surat:  Jenis surat yang diajukan (mis. "SURAT_PENGANTAR").
        keperluan:    Keterangan keperluan pengajuan surat.

    Returns:
        dict berisi pengajuan_id, tracking_code, jenis_surat, current_status, tanggal_pengajuan.
    """
    url: str = f"{_BASE_URL}/surat/pengajuan"
    headers: dict[str, str] = _build_headers(access_token=access_token)
    payload: dict[str, str] = {
        "jenis_surat": jenis_surat,
        "keperluan": keperluan,
    }

    return _request_with_retry("POST", url, headers, json_body=payload)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: track_pengajuan_surat → GET /surat/pengajuan/track/{tracking_code}
# ─────────────────────────────────────────────────────────────────────────────
def track_pengajuan_surat(tracking_code: str) -> dict[str, Any]:
    """
    Melacak status pengajuan surat menggunakan kode pelacakan publik.
    Endpoint: GET /surat/pengajuan/track/{tracking_code} (API_SPECIFICATION.md §3.4.2)
    Akses: Publik — tidak memerlukan token.

    tracking_code berfungsi sebagai bentuk verifikasi kepemilikan pengajuan.

    Args:
        tracking_code: Kode pelacakan (mis. "SRT-20260812-A8F3K2").

    Returns:
        dict berisi tracking_code, jenis_surat, current_status, nomor_surat, dan riwayat_status.

    Raises:
        ClientError: 404 jika tracking_code tidak ditemukan.
    """
    url: str = f"{_BASE_URL}/surat/pengajuan/track/{tracking_code}"
    headers: dict[str, str] = _build_headers()  # Tidak perlu token — akses publik

    return _request_with_retry("GET", url, headers)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: list_pengajuan_surat → GET /surat/pengajuan
# ─────────────────────────────────────────────────────────────────────────────
def list_pengajuan_surat(
    access_token: str,
    current_status: str | None = None,
    jenis_surat: str | None = None,
    rt_code: str | None = None,
    page: int = 1,
    per_page: int = 15,
) -> dict[str, Any]:
    """
    Mengambil daftar pengajuan surat untuk keperluan verifikasi pengurus (area-scoped).
    Endpoint: GET /surat/pengajuan (API_SPECIFICATION.md §3.4.3)
    Akses: KETUA_RT, SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.

    ATURAN AREA SCOPING (AGENTS.md §2.1):
    Filter rt_code WAJIB dihormati — Agent tidak boleh membangun query
    yang melewati filter wilayah RT. Untuk KETUA_RT, backend memaksakan
    rt_code ke wilayah milik user meskipun parameter dikirim berbeda.

    Args:
        access_token:   Bearer token.
        current_status: Filter status ("SUBMITTED", "RT_REVIEW", "RW_REVIEW", "COMPLETED", "REJECTED").
        jenis_surat:    Filter jenis surat.
        rt_code:        Filter wilayah RT — dihormati sesuai area scoping backend.
        page:           Halaman pagination.
        per_page:       Item per halaman.

    Returns:
        dict berisi list pengajuan dan metadata pagination.
    """
    url: str = f"{_BASE_URL}/surat/pengajuan"
    headers: dict[str, str] = _build_headers(access_token=access_token)

    params: dict[str, Any] = {"page": page, "per_page": per_page}
    if current_status is not None:
        params["current_status"] = current_status
    if jenis_surat is not None:
        params["jenis_surat"] = jenis_surat
    if rt_code is not None:
        params["rt_code"] = rt_code

    return _request_with_retry("GET", url, headers, params=params)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: verify_pengajuan_surat → POST /surat/pengajuan/{id}/verify
# WAJIB KONFIRMASI MANUSIA (AGENTS.md §3.1)
# ─────────────────────────────────────────────────────────────────────────────
def verify_pengajuan_surat(
    access_token: str,
    pengajuan_id: int,
    action: str,
    catatan: str | None = None,
    idempotency_key: str | None = None,
    human_confirmed: bool = False,
) -> dict[str, Any]:
    """
    Melakukan verifikasi/persetujuan tahap berjenjang pada satu pengajuan surat.
    Endpoint: POST /surat/pengajuan/{id}/verify (API_SPECIFICATION.md §3.4.4)
    Akses: KETUA_RT, SEKRETARIS_RW, KETUA_RW.

    ⚠️  AKSI INI WAJIB MELALUI KONFIRMASI MANUSIA (AGENTS.md §3.1).
    Fungsi ini MENOLAK eksekusi (PermissionError) jika human_confirmed tidak
    eksplisit True — pola ini diterapkan konsisten di seluruh tool handler.

    Endpoint yang sama digunakan oleh beberapa role (dibedakan oleh role token):
    - KETUA_RT:     SUBMITTED → RT_REVIEW
    - SEKRETARIS_RW / KETUA_RW: RT_REVIEW → RW_REVIEW → COMPLETED

    Mendukung Idempotency-Key (API_SPECIFICATION.md §1.8) untuk mencegah
    duplikasi aksi akibat retry jaringan. Jika tidak diberikan, UUID baru
    dibangkitkan otomatis.

    Args:
        access_token:     Bearer token.
        pengajuan_id:     ID pengajuan surat yang diverifikasi.
        action:           "APPROVE" atau "REJECT".
        catatan:          Catatan verifikasi (opsional).
        idempotency_key:  Header Idempotency-Key; dibangkitkan otomatis jika None.
        human_confirmed:  WAJIB True — harus dikonfirmasi manusia secara eksplisit
                          sebelum memanggil fungsi ini (AGENTS.md §3.1).

    Returns:
        dict berisi pengajuan_id, current_status baru, verified_by, verified_at.

    Raises:
        PermissionError: Jika human_confirmed bukan True (guardrail human-in-the-loop).
        ValueError:      Jika action tidak valid.
        ConflictError:   409 — status pengajuan sudah berubah oleh proses lain.
        ClientError:     403 — role tidak berwenang memverifikasi pada tahap ini.
    """
    # ── GUARDRAIL: human-in-the-loop wajib (AGENTS.md §3.1) ──────────────────
    if human_confirmed is not True:
        raise PermissionError(
            "verify_pengajuan_surat memerlukan konfirmasi eksplisit manusia. "
            "Setel human_confirmed=True setelah mendapatkan persetujuan pengguna "
            "sebelum memanggil fungsi ini. (AGENTS.md §3.1)"
        )
    # ─────────────────────────────────────────────────────────────────────────

    valid_actions = {"APPROVE", "REJECT"}
    if action not in valid_actions:
        raise ValueError(f"action harus salah satu dari: {valid_actions}. Diterima: {action!r}")

    # Bangkitkan Idempotency-Key jika tidak diberikan (API_SPECIFICATION.md §1.8)
    final_idempotency_key: str = idempotency_key if idempotency_key else str(uuid.uuid4())

    url: str = f"{_BASE_URL}/surat/pengajuan/{pengajuan_id}/verify"
    headers: dict[str, str] = _build_headers(
        access_token=access_token,
        idempotency_key=final_idempotency_key,
    )
    payload: dict[str, Any] = {"action": action}
    if catatan is not None:
        payload["catatan"] = catatan

    return _request_with_retry("POST", url, headers, json_body=payload)


# ─────────────────────────────────────────────────────────────────────────────
# EXCEPTION CLASSES (RULES.md §3.1)
# ─────────────────────────────────────────────────────────────────────────────
class PesuratanToolError(Exception):
    """Base exception untuk seluruh persuratan tool handler."""


class RateLimitError(PesuratanToolError):
    """429 — caller wajib menghormati retry_after_seconds."""

    def __init__(self, message: str, retry_after_seconds: int = 60) -> None:
        super().__init__(message)
        self.retry_after_seconds: int = retry_after_seconds


class ClientError(PesuratanToolError):
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


class ConflictError(PesuratanToolError):
    """409 Conflict — status sudah berubah; muat ulang data terbaru."""

    def __init__(self, message: str, body: dict[str, Any] | None = None) -> None:
        super().__init__(message)
        self.body: dict[str, Any] = body or {}


class ServerError(PesuratanToolError):
    """5xx atau koneksi gagal setelah retry habis — laporkan ke Orchestrator."""

    def __init__(self, message: str, status_code: int | None = None) -> None:
        super().__init__(message)
        self.status_code: int | None = status_code
