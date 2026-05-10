<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class OptimizeCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id,is_active,1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id,is_active,1'],
            'zone_id' => ['nullable', 'integer', 'exists:geo_zones,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');

        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = is_array($decoded) ? $decoded : [];
        }

        $this->merge([
            'items' => $items,
            'store_id' => $this->input('store_id') ?: null,
            'zone_id' => $this->input('zone_id') ?: null,
        ]);
    }
}
