"""
kependudukan_tools.py
SIM Layanan Warga RW 047 — Handler: Modul Kependudukan
Versi: 1.0
Referensi: docs/API_SPECIFICATION.md §3.3 | .agents/AGENTS.md §2.1, §2.3, §3.1, §3.2, §3.3

Mengimplementasikan tool handler untuk endpoint Kependudukan:
  - list_kartu_keluarga  → GET  /kartu-keluarga
  - create_kartu_keluarga → POST /kartu-keluarga
  - create_warga          → POST /warga
  - get_warga_detail      → GET  /warga/{nik_hash}
  - update_warga          → PATCH /warga/{nik_hash}
  - verify_warga          → PATCH /warga/{nik_hash}/verify

LARANGAN KEAMANAN (AGENTS.md §3.2, RULES.md §2.3):
  - NIK dan No. KK DILARANG muncul dalam bentuk unmasked pada log, output chat,
    atau parameter URL — path parameter menggunakan nik_hash (HMAC-SHA256 dari NIK).
  - Seluruh konfigurasi wajib dari environment variable, tidak ada hardcode.

Dependensi runtime yang dibutuhkan:
  - httpx — konfirmasikan ketersediaan ke pengguna (RULES.md §2.2).
"""

from __future__ import annotations

import hmac
import hashlib
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

# Kunci hash untuk NIK/KK — WAJIB dari .env, DILARANG hardcode (AGENTS.md §3.2, RULES.md §2.3)
_DATA_SEARCH_HASH_KEY: str = os.environ["DATA_SEARCH_HASH_KEY"]


# ─────────────────────────────────────────────────────────────────────────────
# Utilitas: Validasi & hashing NIK/KK
# ─────────────────────────────────────────────────────────────────────────────
def _validate_nik(nik: str) -> None:
    """
    Validasi NIK: wajib 16 digit angka.
    Dilakukan SEBELUM pemanggilan tool sesuai AGENTS.md §2.1.
    Melempar ValueError jika tidak valid.
    """
    if not re.fullmatch(r"\d{16}", nik):
        raise ValueError("NIK harus terdiri dari tepat 16 digit angka.")


def _validate_no_kk(no_kk: str) -> None:
    """
    Validasi No. KK: wajib 16 digit angka.
    Dilakukan SEBELUM pemanggilan tool sesuai AGENTS.md §2.1.
    Melempar ValueError jika tidak valid.
    """
    if not re.fullmatch(r"\d{16}", no_kk):
        raise ValueError("No. KK harus terdiri dari tepat 16 digit angka.")


def compute_nik_hash(nik: str) -> str:
    """
    Hitung HMAC-SHA256 dari NIK menggunakan DATA_SEARCH_HASH_KEY dari .env.
    Digunakan sebagai path parameter nik_hash pada endpoint /warga/{nik_hash}
    agar NIK asli tidak pernah muncul di URL/log (AGENTS.md §3.2, API_SPECIFICATION.md §3.3.4).

    NIK yang diteruskan ke fungsi ini DILARANG di-log oleh caller.
    """
    _validate_nik(nik)
    return hmac.new(
        _DATA_SEARCH_HASH_KEY.encode("utf-8"),
        nik.encode("utf-8"),
        hashlib.sha256,
    ).hexdigest()


def _build_headers(access_token: str) -> dict[str, str]:
    """Bangun HTTP header standar dengan Bearer token."""
    return {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Authorization": f"Bearer {access_token}",
    }


def _handle_response(response: httpx.Response) -> dict[str, Any]:
    """
    Parsing response ke dict. Melempar exception bermakna per kode status.
    Sesuai AGENTS.md §3.3 dan RULES.md §3.1.
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
        raise ConflictError(body.get("message", "Konflik data"), body=body)

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
# TOOL: list_kartu_keluarga → GET /kartu-keluarga
# ─────────────────────────────────────────────────────────────────────────────
def list_kartu_keluarga(
    access_token: str,
    rt_code: str | None = None,
    search: str | None = None,
    page: int = 1,
    per_page: int = 15,
) -> dict[str, Any]:
    """
    Mengambil daftar Kartu Keluarga sesuai kewenangan (area-scoped untuk Ketua RT).
    Endpoint: GET /kartu-keluarga (API_SPECIFICATION.md §3.3.1)
    Akses: KETUA_RT (lingkup RT sendiri), SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.

    Response selalu menggunakan no_kk_masked — data unmasked hanya via endpoint detail
    (AGENTS.md §2.1, SYSTEM_ARCHITECTURE.md §4.4).

    Args:
        access_token: Bearer token yang aktif.
        rt_code:      Filter wilayah RT (untuk KETUA_RT, backend memaksakan ke RT milik user).
        search:       Pencarian berdasarkan alamat/blok.
        page:         Nomor halaman pagination.
        per_page:     Jumlah item per halaman (maks 100).

    Returns:
        dict berisi list data KK (masked) dan metadata pagination.
    """
    url: str = f"{_BASE_URL}/kartu-keluarga"
    headers: dict[str, str] = _build_headers(access_token)

    params: dict[str, Any] = {"page": page, "per_page": per_page}
    if rt_code is not None:
        params["rt_code"] = rt_code
    if search is not None:
        params["search"] = search

    with httpx.Client(timeout=_DEFAULT_TIMEOUT) as client:
        response = client.get(url, headers=headers, params=params)
    return _handle_response(response)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: create_kartu_keluarga → POST /kartu-keluarga
# ─────────────────────────────────────────────────────────────────────────────
def create_kartu_keluarga(
    access_token: str,
    no_kk: str,
    rt_code: str,
    alamat_lengkap: str,
    blok: str,
    nomor_rumah: str,
    status_kepemilikan_rumah: str,
) -> dict[str, Any]:
    """
    Mendaftarkan Kartu Keluarga baru.
    Endpoint: POST /kartu-keluarga (API_SPECIFICATION.md §3.3.2)
    Akses: KETUA_RT (RT sendiri), SEKRETARIS_RW, SUPER_ADMIN.

    Validasi no_kk (16 digit) dilakukan SEBELUM pemanggilan API (AGENTS.md §2.1).

    Args:
        access_token:              Bearer token.
        no_kk:                    Nomor Kartu Keluarga (16 digit angka).
        rt_code:                  Kode RT (mis. "001").
        alamat_lengkap:           Alamat lengkap KK.
        blok:                     Blok/cluster perumahan.
        nomor_rumah:              Nomor rumah.
        status_kepemilikan_rumah: Mis. "Milik Sendiri", "Kontrak".

    Returns:
        dict berisi id, no_kk_masked, rt_code, status, created_at.

    Raises:
        ValueError:    Jika no_kk tidak 16 digit (pra-validasi).
        ConflictError: Jika no_kk sudah terdaftar (409 Conflict).
    """
    _validate_no_kk(no_kk)

    url: str = f"{_BASE_URL}/kartu-keluarga"
    headers: dict[str, str] = _build_headers(access_token)
    payload: dict[str, str] = {
        "no_kk": no_kk,
        "rt_code": rt_code,
        "alamat_lengkap": alamat_lengkap,
        "blok": blok,
        "nomor_rumah": nomor_rumah,
        "status_kepemilikan_rumah": status_kepemilikan_rumah,
    }

    return _request_with_retry("POST", url, headers, json_body=payload)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: create_warga → POST /warga
# ─────────────────────────────────────────────────────────────────────────────
def create_warga(
    access_token: str,
    nik: str,
    no_kk: str,
    nama_lengkap: str,
    jenis_kelamin: str,
    tempat_lahir: str,
    tanggal_lahir: str,
    pekerjaan: str,
    nomor_hp: str,
    status_hubungan_keluarga: str,
    status_warga: str,
) -> dict[str, Any]:
    """
    Menambahkan data warga baru. Status awal tersimpan sebagai MENUNGGU_VERIFIKASI.
    Endpoint: POST /warga (API_SPECIFICATION.md §3.3.3)
    Akses: KETUA_RT, SEKRETARIS_RW.

    Validasi NIK dan No. KK (masing-masing 16 digit) dilakukan SEBELUM panggilan API
    (AGENTS.md §2.1).

    NIK dan No. KK DILARANG di-log — sesuai AGENTS.md §3.2 dan RULES.md §2.3.

    Args:
        access_token:             Bearer token.
        nik:                      NIK warga (16 digit) — tidak di-log.
        no_kk:                    No. KK (16 digit) — tidak di-log.
        nama_lengkap:             Nama lengkap sesuai KTP.
        jenis_kelamin:            "L" atau "P".
        tempat_lahir:             Kota/kabupaten tempat lahir.
        tanggal_lahir:            Format YYYY-MM-DD.
        pekerjaan:                Jenis pekerjaan.
        nomor_hp:                 Nomor HP aktif.
        status_hubungan_keluarga: "Kepala Keluarga", "Istri", "Anak", dst.
        status_warga:             "TETAP" atau "TIDAK_TETAP".

    Returns:
        dict berisi nik_masked, nama_lengkap, verification_status = MENUNGGU_VERIFIKASI.

    Raises:
        ValueError: Jika NIK atau No. KK tidak valid (pra-validasi).
    """
    _validate_nik(nik)
    _validate_no_kk(no_kk)

    url: str = f"{_BASE_URL}/warga"
    headers: dict[str, str] = _build_headers(access_token)
    payload: dict[str, Any] = {
        "nik": nik,
        "no_kk": no_kk,
        "nama_lengkap": nama_lengkap,
        "jenis_kelamin": jenis_kelamin,
        "tempat_lahir": tempat_lahir,
        "tanggal_lahir": tanggal_lahir,
        "pekerjaan": pekerjaan,
        "nomor_hp": nomor_hp,
        "status_hubungan_keluarga": status_hubungan_keluarga,
        "status_warga": status_warga,
    }

    return _request_with_retry("POST", url, headers, json_body=payload)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: get_warga_detail → GET /warga/{nik_hash}
# ─────────────────────────────────────────────────────────────────────────────
def get_warga_detail(access_token: str, nik_hash: str) -> dict[str, Any]:
    """
    Mengambil detail satu data warga berdasarkan hash NIK.
    Endpoint: GET /warga/{nik_hash} (API_SPECIFICATION.md §3.3.4)
    Akses: KETUA_RT (RT sendiri), SEKRETARIS_RW, KETUA_RW, SUPER_ADMIN.

    Path parameter menggunakan nik_hash (HMAC-SHA256), bukan NIK plaintext,
    agar NIK asli tidak muncul di URL atau log server (AGENTS.md §2.1, §3.2).
    Gunakan compute_nik_hash(nik) untuk mendapatkan nik_hash dari NIK.

    Args:
        access_token: Bearer token.
        nik_hash:     Hash HMAC-SHA256 dari NIK (diperoleh dari response list warga
                      atau via compute_nik_hash()).

    Returns:
        dict berisi nik_masked, nama_lengkap, dan data detail warga lainnya.

    Raises:
        ClientError: 404 jika warga tidak ditemukan atau di luar kewenangan.
    """
    url: str = f"{_BASE_URL}/warga/{nik_hash}"
    headers: dict[str, str] = _build_headers(access_token)

    return _request_with_retry("GET", url, headers)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: update_warga → PATCH /warga/{nik_hash}
# ─────────────────────────────────────────────────────────────────────────────
def update_warga(
    access_token: str,
    nik_hash: str,
    fields_to_update: dict[str, Any],
) -> dict[str, Any]:
    """
    Memperbarui data warga. Perubahan status_warga memicu alur verifikasi Sekretaris RW.
    Endpoint: PATCH /warga/{nik_hash} (API_SPECIFICATION.md §3.3.5)
    Akses: KETUA_RT (mengajukan perubahan), SEKRETARIS_RW (memverifikasi).

    NIK DILARANG disertakan dalam fields_to_update — gunakan nik_hash sebagai identifier.
    Perubahan status_warga memerlukan verifikasi Sekretaris RW setelah disimpan.

    Args:
        access_token:     Bearer token.
        nik_hash:         Hash HMAC-SHA256 dari NIK warga yang diperbarui.
        fields_to_update: Dict berisi field yang ingin diubah (mis. {"pekerjaan": "...", "nomor_hp": "..."}).
                          DILARANG menyertakan "nik" atau "no_kk" plaintext.

    Returns:
        dict berisi nik_masked dan verification_status (MENUNGGU_VERIFIKASI jika ada perubahan penting).
    """
    # Penjagaan: tidak boleh meneruskan NIK atau no_kk plaintext sebagai field update
    if "nik" in fields_to_update or "no_kk" in fields_to_update:
        raise ValueError(
            "Field 'nik' dan 'no_kk' tidak boleh disertakan dalam pembaruan data warga. "
            "Gunakan nik_hash sebagai identifier dan no_kk_masked untuk referensi."
        )

    url: str = f"{_BASE_URL}/warga/{nik_hash}"
    headers: dict[str, str] = _build_headers(access_token)

    return _request_with_retry("PATCH", url, headers, json_body=fields_to_update)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: verify_warga → PATCH /warga/{nik_hash}/verify
# ─────────────────────────────────────────────────────────────────────────────
def verify_warga(
    access_token: str,
    nik_hash: str,
    decision: str,
    rejection_notes: str | None = None,
) -> dict[str, Any]:
    """
    Memverifikasi (menyetujui/menolak) data warga berstatus MENUNGGU_VERIFIKASI.
    Endpoint: PATCH /warga/{nik_hash}/verify (API_SPECIFICATION.md §3.3.6)
    Akses: SEKRETARIS_RW saja (AGENTS.md §2.1).

    Args:
        access_token:     Bearer token milik SEKRETARIS_RW.
        nik_hash:         Hash HMAC-SHA256 dari NIK warga yang diverifikasi.
        decision:         "APPROVED" atau "REJECTED".
        rejection_notes:  Wajib diisi jika decision == "REJECTED".

    Returns:
        dict berisi nik_masked, status_warga, verification_status = TERVERIFIKASI/DITOLAK.

    Raises:
        ValueError:    Jika decision REJECTED tanpa rejection_notes.
        ConflictError: Jika warga tidak sedang berstatus MENUNGGU_VERIFIKASI (409).
        ClientError:   403 jika caller bukan SEKRETARIS_RW.
    """
    valid_decisions = {"APPROVED", "REJECTED"}
    if decision not in valid_decisions:
        raise ValueError(f"decision harus salah satu dari: {valid_decisions}. Diterima: {decision!r}")

    if decision == "REJECTED" and not rejection_notes:
        raise ValueError("rejection_notes wajib diisi ketika decision adalah REJECTED.")

    url: str = f"{_BASE_URL}/warga/{nik_hash}/verify"
    headers: dict[str, str] = _build_headers(access_token)
    payload: dict[str, Any] = {"decision": decision}
    if rejection_notes is not None:
        payload["rejection_notes"] = rejection_notes

    return _request_with_retry("PATCH", url, headers, json_body=payload)


# ─────────────────────────────────────────────────────────────────────────────
# EXCEPTION CLASSES (RULES.md §3.1)
# ─────────────────────────────────────────────────────────────────────────────
class KependudukanToolError(Exception):
    """Base exception untuk seluruh kependudukan tool handler."""


class RateLimitError(KependudukanToolError):
    """429 Too Many Requests — caller wajib menghormati retry_after_seconds."""

    def __init__(self, message: str, retry_after_seconds: int = 60) -> None:
        super().__init__(message)
        self.retry_after_seconds: int = retry_after_seconds


class ClientError(KependudukanToolError):
    """4xx (selain 409 dan 429) — tidak di-retry; analisis dan laporkan."""

    def __init__(
        self,
        message: str,
        status_code: int = 400,
        body: dict[str, Any] | None = None,
    ) -> None:
        super().__init__(message)
        self.status_code: int = status_code
        self.body: dict[str, Any] = body or {}


class ConflictError(KependudukanToolError):
    """409 Conflict — duplikasi data (NIK/KK sudah ada, verifikasi sudah selesai, dll.)."""

    def __init__(self, message: str, body: dict[str, Any] | None = None) -> None:
        super().__init__(message)
        self.body: dict[str, Any] = body or {}


class ServerError(KependudukanToolError):
    """5xx atau koneksi gagal setelah retry habis — laporkan ke Orchestrator."""

    def __init__(self, message: str, status_code: int | None = None) -> None:
        super().__init__(message)
        self.status_code: int | None = status_code
