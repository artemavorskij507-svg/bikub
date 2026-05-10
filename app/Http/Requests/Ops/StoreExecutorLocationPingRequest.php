<?php

namespace App\Http\Requests\Ops;

use App\Domain\Operations\Models\Executor;
use Illuminate\Foundation\Http\FormRequest;

class StoreExecutorLocationPingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Executor|null $executor */
        $executor = $this->route('executor');

        return $executor && ($this->user()?->can('locationPing', $executor) ?? false);
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'recorded_at' => ['nullable', 'date'],
            'assignment_id' => ['nullable', 'integer', 'exists:assignments,id'],
            'service_job_id' => ['nullable', 'integer', 'exists:service_jobs,id'],
        ];
    }
}

