<?php

namespace App\Http\Requests\AgencyAgents;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentStepStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $step = $this->route('step');

        return $step
            ? ($this->user()?->can('updateStatus', $step) ?? false)
            : false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:queued,waiting_dependencies,executing,artifact_generated,validation_failed,needs_revision,ready_for_review,approved,completed,blocked,failed'],
            'validator_passed' => ['nullable', 'boolean'],
            'validation_notes' => ['nullable', 'string'],
            'output_payload' => ['nullable', 'array'],
            'system_note' => ['nullable', 'string'],
        ];
    }
}
