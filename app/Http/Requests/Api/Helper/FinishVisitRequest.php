<?php

namespace App\Http\Requests\Api\Helper;

use Illuminate\Foundation\Http\FormRequest;

class FinishVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date'],
            'summary' => ['required', 'string', 'max:2000'],
            'status' => ['required', 'in:COMPLETED,PARTIALLY_COMPLETED,NOT_COMPLETED'],
            'client_mood' => ['nullable', 'in:HAPPY,NEUTRAL,CONCERNED'],
            'issues_noted' => ['nullable', 'string', 'max:2000'],
            'followup_recommended' => ['boolean'],
            'followup_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'summary.required' => 'Отчёт о визите обязателен для заполнения.',
            'summary.max' => 'Отчёт не должен превышать 2000 символов.',
            'status.required' => 'Статус завершения обязателен.',
            'status.in' => 'Недопустимый статус завершения.',
        ];
    }
}
