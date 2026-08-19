<?php

declare(strict_types=1);

namespace App\Support\Audit;

use App\Models\AuditLog;
use App\Models\Warga;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Throwable;

class AuditService
{
    /**
     * Catat audit log secara terpusat dengan sanitasi field PII.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public static function log(
        string $module,
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
        ?string $ipAddress = null
    ): AuditLog {
        $userId = $userId ?? (int) Auth::id();
        if ($userId === 0) {
            $userId = null;
        }

        $ipAddress = $ipAddress ?? Request::ip();

        return AuditLog::create([
            'user_id' => $userId,
            'module' => $module,
            'action' => $action,
            'entity_type' => $entityType ?? 'system',
            'entity_id' => $entityId ?? '0',
            'old_values' => $oldValues !== null ? self::sanitizeAuditPayload($oldValues) : null,
            'new_values' => $newValues !== null ? self::sanitizeAuditPayload($newValues) : null,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    /**
     * Catat percobaan akses tidak terotorisasi (403 Forbidden) ke audit_logs.
     * Memastikan audit trail mencatat unauthorized access attempts (USER_STORIES.md §3.5)
     * tanpa membocorkan PII atau mengganggu response 403.
     */
    public static function logUnauthorizedAccess(HttpRequest $request, Throwable $e): ?AuditLog
    {
        try {
            $user = $request->user();
            $userId = $user?->id ?: (int) Auth::id();
            if ($userId === 0) {
                $userId = null;
            }

            $ipAddress = $request->ip();
            $path = $request->path();

            // Tentukan modul dari route / path
            $module = 'General';
            if (str_contains($path, 'warga') || str_contains($path, 'kartu-keluarga')) {
                $module = 'Kependudukan';
            } elseif (str_contains($path, 'auth') || str_contains($path, 'users')) {
                $module = 'Auth';
            } elseif (str_contains($path, 'surat')) {
                $module = 'Persuratan';
            } elseif (str_contains($path, 'laporan-aspirasi')) {
                $module = 'LaporanAspirasi';
            } elseif (str_contains($path, 'iuran') || str_contains($path, 'catatan-iuran')) {
                $module = 'Keuangan';
            } elseif (str_contains($path, 'informasi-publik')) {
                $module = 'InformasiPublik';
            } elseif (str_contains($path, 'dashboard')) {
                $module = 'Dashboard';
            }

            // Tentukan entity_type dan entity_id
            $entityType = 'system';
            $entityId = '0';

            if (str_contains($path, 'warga')) {
                $entityType = 'wargas';
                $nikHash = $request->route('nik_hash') ?? (is_string($request->segment(4)) ? $request->segment(4) : null);
                if ($nikHash) {
                    $wargaId = Warga::where('nik_hash', (string) $nikHash)->value('id');
                    $entityId = $wargaId ? (string) $wargaId : (string) $nikHash;
                }
            } elseif (str_contains($path, 'kartu-keluarga')) {
                $entityType = 'kartu_keluargas';
                $kkId = $request->route('id') ?? $request->route('kartu_keluarga') ?? (is_string($request->segment(4)) ? $request->segment(4) : null);
                if ($kkId) {
                    $entityId = (string) $kkId;
                }
            } elseif (str_contains($path, 'users')) {
                $entityType = 'users';
                $userIdParam = $request->route('id') ?? $request->route('user') ?? (is_string($request->segment(4)) ? $request->segment(4) : null);
                if ($userIdParam) {
                    $entityId = (string) $userIdParam;
                }
            }

            $metadata = [
                'outcome' => 'DENIED',
                'status_code' => 403,
                'method' => $request->method(),
                'path' => $request->path(),
                'reason' => $e->getMessage() ?: 'Unauthorized access attempt',
            ];

            return self::log(
                module: $module,
                action: 'UNAUTHORIZED_ACCESS_ATTEMPT',
                entityType: $entityType,
                entityId: $entityId,
                oldValues: null,
                newValues: $metadata,
                userId: $userId,
                ipAddress: $ipAddress
            );
        } catch (Throwable) {
            // Guardrail: Audit failure must never cause 500 or alter response
            return null;
        }
    }

    /**
     * Memastikan tidak ada plaintext NIK / No. KK / data sensitif mentah yang lolos ke audit log.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function sanitizeAuditPayload(array $payload): array
    {
        $sanitized = [];
        $sensitiveKeys = [
            'nik',
            'no_kk',
            'password',
            'password_confirmation',
            'token',
            'alamat_lengkap',
            'alamat',
            'nomor_hp',
            'no_hp',
            'tempat_lahir',
            'tanggal_lahir',
        ];

        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                continue; // Jangan masukkan field mentah ini ke audit log
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeAuditPayload($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
