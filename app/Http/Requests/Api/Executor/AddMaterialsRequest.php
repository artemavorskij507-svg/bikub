<?php

namespace App\Http\Requests\Api\Executor;

use Illuminate\Foundation\Http\FormRequest;

class AddMaterialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:32'],
            'unit_price_minor' => ['nullable', 'integer', 'min:0'],
            'total_price_minor' => ['nullable', 'integer', 'min:0'],
            'purchased_at' => ['nullable', 'date'],
            'receipt_url' => ['nullable', 'string', 'max:2048'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
