<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoadsideHelpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Публичная форма, доступна всем
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],

            'service_type' => ['required', 'in:roadside_assistance,vehicle_transport,vehicle_inspection'],

            'vehicle_make' => ['nullable', 'string', 'max:255'],
            'vehicle_model' => ['nullable', 'string', 'max:255'],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],

            'problem_type' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'location_address' => ['nullable', 'string', 'max:500'],
            'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['nullable', 'numeric', 'between:-180,180'],

            'destination_address' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Пожалуйста, укажите ваше имя',
            'phone.required' => 'Пожалуйста, укажите ваш телефон',
            'service_type.required' => 'Пожалуйста, выберите тип услуги',
            'service_type.in' => 'Выбран недопустимый тип услуги',
        ];
    }
}
