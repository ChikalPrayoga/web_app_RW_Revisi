"""
auth_tools.py
SIM Layanan Warga RW 047 — Handler: Modul Auth
Versi: 1.0
Referensi: docs/API_SPECIFICATION.md §3.1 | .agents/AGENTS.md §2.1, §2.3, §3.1, §3.3

Mengimplementasikan tool handler untuk endpoint Auth:
  - auth_login     → POST /auth/login
  - auth_logout    → POST /auth/logout
  - get_current_user → GET /auth/me

LARANGAN (RULES.md §2.3): Tidak ada API key / credential hardcode.
Seluruh konfigurasi wajib diambil dari environment variable.

Dependensi runtime yang dibutuhkan:
  - httpx (HTTP client async) — verifikasi ketersediaan ke pengguna (RULES.md §2.2)
    Belum tentu terpasang; konfirmasikan sebelum eksekusi.
  - python-dotenv (opsional, untuk development lokal)
"""

from __future__ import annotations

import os
import time
from typing import Any

import httpx

# ─────────────────────────────────────────────────────────────────────────────
# Konfigurasi dari environment variable (RULES.md §2.3 — DILARANG hardcode)
# ─────────────────────────────────────────────────────────────────────────────
_BASE_URL: str = os.environ["API_BASE_URL"].rstrip("/")
# Contoh nilai di .env:  API_BASE_URL=http://localhost:8000/api/v1

_DEFAULT_TIMEOUT: float = float(os.environ.get("API_TIMEOUT_SECONDS", "30"))
_MAX_RETRY_5XX: int = int(os.environ.get("API_MAX_RETRIES", "3"))
_RETRY_BACKOFF_BASE: float = float(os.environ.get("API_RETRY_BACKOFF_BASE", "2.0"))

# Rate limit khusus auth/login sesuai API_SPECIFICATION.md §2.5
_LOGIN_RATE_LIMIT_PER_MINUTE: int = 5


def _build_headers(access_token: str | None = None) -> dict[str, str]:
    """Bangun HTTP header standar. Token disertakan hanya jika diberikan."""
    headers: dict[str, str] = {
        "Accept": "application/json",
        "Content-Type": "application/json",
    }
    if access_token is not None:
        headers["Authorization"] = f"Bearer {access_token}"
    return headers


def _handle_response(response: httpx.Response) -> dict[str, Any]:
    """
    Parsing response API ke dict Python. Melempar exception bermakna sesuai
    kode status HTTP (RULES.md §3.1).

    Sesuai AGENTS.md §3.3:
    - 4xx → RuntimeError (tidak di-retry otomatis)
    - 5xx → ditangani di _request_with_retry dengan exponential backoff
    - 429 → RateLimitError (caller wajib menghormati Retry-After)
    """
    try:
        body: dict[str, Any] = response.json()
    except Exception:
        body = {"success": False, "message": f"Respons bukan JSON valid (status {response.status_code})"}

    if response.status_code == 429:
        retry_after: int = int(response.headers.get("Retry-After", "60"))
        raise RateLimitError(
            f"Rate limit tercapai. Tunggu {retry_after} detik sebelum mencoba lagi.",
            retry_after_seconds=retry_after,
        )

    if response.is_client_error:
        # 4xx — jangan retry; analisis dan laporkan (AGENTS.md §3.3)
        message: str = body.get("message", f"Client error {response.status_code}")
        raise ClientError(message, status_code=response.status_code, body=body)

    return body


def _request_with_retry(
    method: str,
    url: str,
    headers: dict[str, str],
    json_body: dict[str, Any] | None = None,
) -> dict[str, Any]:
    """
    Kirim HTTP request dengan exponential backoff untuk 5xx (maks 3 kali).
    Sesuai AGENTS.md §3.3 — tidak melakukan retry untuk 4xx.
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

            # Jika 5xx dan masih ada sisa percobaan, delay lalu retry
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
            raise ServerError(
                f"Koneksi gagal setelah {_MAX_RETRY_5XX} percobaan: {exc}"
            ) from exc

    raise ServerError(
        f"Server tidak merespons setelah {_MAX_RETRY_5XX} percobaan."
    ) from last_error


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: auth_login → POST /auth/login
# ─────────────────────────────────────────────────────────────────────────────
def auth_login(email: str, password: str) -> dict[str, Any]:
    """
    Autentikasi pengguna dan menerbitkan access token.
    Endpoint: POST /auth/login (API_SPECIFICATION.md §3.1.1)
    Akses: Publik — tidak memerlukan token.

    Rate limit: maks 5 percobaan/menit per IP+email (API_SPECIFICATION.md §2.5).
    Sesuai AGENTS.md §2.1: tidak boleh di-retry otomatis oleh Agent tanpa jeda.

    Args:
        email:    Email pengguna (mis. ketuart01@rw047.id)
        password: Kata sandi pengguna (DILARANG di-log — RULES.md §2.3)

    Returns:
        dict berisi access_token, token_type, expires_at, dan data user.

    Raises:
        RateLimitError: Jika 429 diterima — caller WAJIB menghormati Retry-After.
        ClientError:    Jika kredensial salah (422) atau format salah (400/422).
        ServerError:    Jika 5xx setelah retry habis.
    """
    url: str = f"{_BASE_URL}/auth/login"
    headers: dict[str, str] = _build_headers()
    payload: dict[str, str] = {"email": email, "password": password}
    # CATATAN: password tidak di-log, sesuai RULES.md §2.3 dan models.config.yaml

    return _request_with_retry("POST", url, headers, json_body=payload)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: auth_logout → POST /auth/logout
# ─────────────────────────────────────────────────────────────────────────────
def auth_logout(access_token: str) -> dict[str, Any]:
    """
    Mencabut token akses aktif (invalidasi sesi).
    Endpoint: POST /auth/logout (API_SPECIFICATION.md §3.1.2)
    Akses: Terautentikasi — seluruh role.

    Args:
        access_token: Bearer token yang aktif saat ini.

    Returns:
        dict konfirmasi logout berhasil.

    Raises:
        ClientError: Jika token tidak valid (401).
        ServerError: Jika 5xx setelah retry habis.
    """
    url: str = f"{_BASE_URL}/auth/logout"
    headers: dict[str, str] = _build_headers(access_token=access_token)

    return _request_with_retry("POST", url, headers, json_body=None)


# ─────────────────────────────────────────────────────────────────────────────
# TOOL: get_current_user → GET /auth/me
# ─────────────────────────────────────────────────────────────────────────────
def get_current_user(access_token: str) -> dict[str, Any]:
    """
    Mengambil data profil pengguna yang sedang login berdasarkan token aktif.
    Endpoint: GET /auth/me (API_SPECIFICATION.md §3.1.3)
    Akses: Terautentikasi — seluruh role.

    Digunakan oleh semua Worker Agent untuk validasi konteks peran
    sebelum mengeksekusi tool yang memerlukan otorisasi (AGENTS.md §2.1).

    Args:
        access_token: Bearer token yang aktif.

    Returns:
        dict profil pengguna: id, username, full_name, email, role, rt_code, status, last_login_at.

    Raises:
        ClientError: Jika token tidak valid atau sudah kedaluwarsa (401).
        ServerError: Jika 5xx setelah retry habis.
    """
    url: str = f"{_BASE_URL}/auth/me"
    headers: dict[str, str] = _build_headers(access_token=access_token)

    return _request_with_retry("GET", url, headers)


# ─────────────────────────────────────────────────────────────────────────────
# EXCEPTION CLASSES (RULES.md §3.1 — custom exception, bukan generic Exception)
# ─────────────────────────────────────────────────────────────────────────────
class AuthToolError(Exception):
    """Base exception untuk seluruh auth tool handler."""


class RateLimitError(AuthToolError):
    """
    Dilempar saat API mengembalikan 429 Too Many Requests.
    Caller WAJIB menghentikan pemanggilan berulang dan menunggu
    sesuai retry_after_seconds (AGENTS.md §3.3).
    """

    def __init__(self, message: str, retry_after_seconds: int = 60) -> None:
        super().__init__(message)
        self.retry_after_seconds: int = retry_after_seconds


class ClientError(AuthToolError):
    """
    Dilempar untuk error 4xx (kecuali 429).
    Agent TIDAK melakukan retry otomatis — analisis error dan perbaiki payload
    atau laporkan ke Orchestrator (AGENTS.md §3.3).
    """

    def __init__(
        self,
        message: str,
        status_code: int = 400,
        body: dict[str, Any] | None = None,
    ) -> None:
        super().__init__(message)
        self.status_code: int = status_code
        self.body: dict[str, Any] = body or {}


class ServerError(AuthToolError):
    """
    Dilempar untuk error 5xx atau koneksi gagal setelah retry habis.
    Laporkan ke Orchestrator sebagai blocker (AGENTS.md §3.3).
    """

    def __init__(self, message: str, status_code: int | None = None) -> None:
        super().__init__(message)
        self.status_code: int | None = status_code
