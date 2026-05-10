<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonalShopperOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')->where('is_active', true),
            ],
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'products.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $products = $this->input('products');

        if (is_string($products)) {
            $decoded = json_decode($products, true);
            $products = is_array($decoded) ? $decoded : [];
        }

        $normalised = collect($products ?: [])
            ->map(function ($item) {
                return [
                    'id' => isset($item['id']) ? (int) $item['id'] : null,
                    'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : null,
                ];
            })
            ->toArray();

        $this->merge([
            'products' => $normalised,
            'store_id' => $this->input('store_id') ? (int) $this->input('store_id') : null,
        ]);
    }
}
