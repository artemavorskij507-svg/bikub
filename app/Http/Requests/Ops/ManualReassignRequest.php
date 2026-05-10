<?php

namespace App\Http\Requests\Ops;

use Illuminate\Foundation\Http\FormRequest;

class ManualReassignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job && ($this->user()?->can('update', $job) ?? false);
    }

    public function rules(): array
    {
        return [
            'executor_id' => ['required', 'integer', 'exists:executors,id'],
            'reason' => ['nullable', 'string', 'max:500'],
            'expected_job_version' => ['required', 'string', 'max:64'],
        ];
    }
}
