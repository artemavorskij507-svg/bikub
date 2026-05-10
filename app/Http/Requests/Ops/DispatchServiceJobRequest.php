<?php

namespace App\Http\Requests\Ops;

use App\Domain\Operations\Models\ServiceJob;
use Illuminate\Foundation\Http\FormRequest;

class DispatchServiceJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ServiceJob|null $job */
        $job = $this->route('job');

        return $job && ($this->user()?->can('dispatch', $job) ?? false);
    }

    public function rules(): array
    {
        return [
            'mode' => ['nullable', 'in:auto,manual,broadcast,dispatcher'],
            'executor_id' => ['nullable', 'integer', 'exists:executors,id'],
            'force' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

