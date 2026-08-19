<?php

declare(strict_types=1);

namespace App\Modules\Keuangan\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IuranTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'default_amount' => (float) $this->default_amount,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
