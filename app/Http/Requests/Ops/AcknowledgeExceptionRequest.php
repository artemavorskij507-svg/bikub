<?php

namespace App\Http\Requests\Ops;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exception = $this->route('exception');

        return $exception && ($this->user()?->can('update', $exception) ?? false);
    }

    public function rules(): array
    {
        return [
            'expected_exception_version' => ['required', 'string', 'max:64'],
        ];
    }
}
