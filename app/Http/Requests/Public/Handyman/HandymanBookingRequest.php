<?php

namespace App\Http\Requests\Public\Handyman;

use Illuminate\Foundation\Http\FormRequest;

class HandymanBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null; // или разреши гостям, если есть guest-flow
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:4000'],
            'context_notes' => ['nullable', 'string', 'max:4000'],
            'needs_materials_purchase' => ['sometimes', 'boolean'],
            'materials_notes' => ['nullable', 'string', 'max:2000'],
            'expected_duration_minutes' => ['nullable', 'integer', 'min:30', 'max:480'],
            'address_line' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:16'],
            'city' => ['required', 'string', 'max:64'],
            'desired_date' => ['required', 'date', 'after_or_equal:today'],
            'desired_time_from' => ['required', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
            'desired_time_to' => ['required', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
            // attachments можно добавить позже, через отдельный механизм загрузки файлов
        ];
    }
}
