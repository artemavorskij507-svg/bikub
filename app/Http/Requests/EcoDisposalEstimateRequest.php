<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EcoDisposalEstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adjust if only authenticated users are allowed
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.disposal_item_id' => ['required', 'integer', 'exists:disposal_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],

            'floor' => ['nullable', 'integer', 'min:0', 'max:50'],
            'has_elevator' => ['nullable', 'boolean'],
            'parking_distance_m' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'express_requested' => ['nullable', 'boolean'],
            'zone_code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
