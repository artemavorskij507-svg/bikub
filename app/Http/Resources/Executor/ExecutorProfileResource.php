<?php

namespace App\Http\Resources\Executor;

use Illuminate\Http\Resources\Json\JsonResource;

class ExecutorProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->user?->name,
            'phone' => $this->user?->phone ?? null,
            'email' => $this->user?->email,
            'skills' => $this->skills ?? [],
            'rating' => (float) ($this->rating ?? 0),
            'completed_orders_count' => (int) ($this->completed_orders_count ?? 0),
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'is_active' => (bool) $this->is_active,
            'vehicle_type' => $this->vehicle_type,
        ];
    }
}
