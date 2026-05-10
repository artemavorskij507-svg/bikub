<?php

namespace App\Http\Requests\Ops;

use Illuminate\Foundation\Http\FormRequest;

class ResolveExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exception = $this->route('exception');

        return $exception && ($this->user()?->can('resolve', $exception) ?? false);
    }

    public function rules(): array
    {
        return [
            'resolution_code' => ['required', 'string', 'max:100'],
            'resolution_notes' => ['nullable', 'string', 'max:5000'],
            'root_cause' => ['nullable', 'string', 'max:255'],
            'expected_exception_version' => ['required', 'string', 'max:64'],
        ];
    }
}
