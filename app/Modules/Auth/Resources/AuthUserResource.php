<?php

declare(strict_types=1);

namespace App\Modules\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource untuk data profil user yang disertakan dalam response auth.
 * Memastikan field yang dikembalikan konsisten dengan kontrak API_SPECIFICATION.md §3.1.
 * Password dan token tidak pernah ikut dalam response ini.
 */
class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'role' => $this->role?->name,
            'rt_code' => $this->rt_code,
            'status' => $this->status,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
