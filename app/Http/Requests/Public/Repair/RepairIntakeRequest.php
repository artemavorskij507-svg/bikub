<?php

namespace App\Http\Requests\Public\Repair;

use Illuminate\Foundation\Http\FormRequest;

class RepairIntakeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // либо требуй логин, как в других публичных модулях
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_title' => ['nullable', 'string', 'max:255'],
            'object_type' => ['required', 'string', 'max:64'], // квартира/дом/офис
            'area_sqm' => ['nullable', 'numeric', 'min:1', 'max:10000'],
            'repair_type' => ['required', 'string', 'max:64'], // косметический/капитальный/офис
            'desired_start_at' => ['nullable', 'date'],
            'desired_finish_at' => ['nullable', 'date', 'after_or_equal:desired_start_at'],
            'budget_expectation' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:8000'],
            'address_line' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:16'],
            'city' => ['required', 'string', 'max:64'],
            'design_project_url' => ['nullable', 'url', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Пожалуйста, опишите объект и желаемый ремонт.',
            'address_line.required' => 'Укажите адрес объекта.',
        ];
    }
}
