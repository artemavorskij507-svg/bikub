<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PriceEstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_type' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:120'],
            'zone' => ['nullable', 'string', 'max:120'],
            'from_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'from_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'to_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'to_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'total_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'total_volume_m3' => ['nullable', 'numeric', 'min:0'],
            'scheduled_at' => ['nullable', 'date'],
            'is_urgent' => ['sometimes', 'boolean'],
            'items' => ['array'],
            'items.*.name' => ['nullable', 'string'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'min:0'],
            'items.*.volume_m3' => ['nullable', 'numeric', 'min:0'],
            'items.*.category' => ['nullable', 'string', 'max:120'],
        ];
    }
}
