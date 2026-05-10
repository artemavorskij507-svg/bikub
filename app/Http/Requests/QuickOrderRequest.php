<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow both authenticated and guest users
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'service_type_id' => ['required', 'integer', 'exists:service_types,id'],
            'address' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'service_type_id.required' => 'Будь ласка, оберіть тип послуги',
            'service_type_id.exists' => 'Обраний тип послуги не існує',
            'address.required' => 'Будь ласка, введіть адресу',
            'address.min' => 'Адреса повинна містити мінімум 5 символів',
            'address.max' => 'Адреса не може перевищувати 255 символів',
        ];
    }
}
