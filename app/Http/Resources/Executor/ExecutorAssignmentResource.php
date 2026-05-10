<?php

namespace App\Http\Resources\Executor;

use Illuminate\Http\Resources\Json\JsonResource;

class ExecutorAssignmentResource extends JsonResource
{
    public function toArray($request): array
    {
        $order = $this->order;
        $details = $order?->handymanDetails;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'is_primary' => (bool) $this->is_primary,
            'score' => $this->score,
            'planned_start_at' => $this->planned_start_at?->toIso8601String(),
            'planned_finish_at' => $this->planned_finish_at?->toIso8601String(),
            'actual_start_at' => $this->actual_start_at?->toIso8601String(),
            'actual_finish_at' => $this->actual_finish_at?->toIso8601String(),

            'order' => [
                'id' => $order?->id,
                'order_number' => $order?->order_number,
                'service_type' => $order?->service_type,
                'status' => $order?->status,
                'estimated_total' => $order?->estimated_total,
            ],

            'details' => $details ? [
                'description' => $details->description,
                'context_notes' => $details->context_notes,
                'needs_materials_purchase' => (bool) $details->needs_materials_purchase,
                'materials_notes' => $details->materials_notes,
                'expected_duration_minutes' => $details->expected_duration_minutes,
                'address' => [
                    'line' => $details->address_line,
                    'postal_code' => $details->postal_code,
                    'city' => $details->city,
                ],
                'desired_start_at' => $details->desired_start_at?->toIso8601String(),
                'desired_finish_at' => $details->desired_finish_at?->toIso8601String(),
                'estimated_price_minor' => $details->estimated_price_minor,
            ] : null,
        ];
    }
}
