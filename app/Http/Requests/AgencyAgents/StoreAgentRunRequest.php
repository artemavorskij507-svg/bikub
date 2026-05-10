<?php

namespace App\Http\Requests\AgencyAgents;

use App\Domain\AgentOS\Models\AgentRun;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgentRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AgentRun::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'uuid'],
            'tenant_id' => ['nullable', 'integer'],
            'goal' => ['required', 'string', 'max:5000'],
            'risk_level' => ['nullable', 'in:low,medium,high,critical'],
            'requires_approval' => ['nullable', 'boolean'],
            'deployment_allowed' => ['nullable', 'boolean'],
            'idempotency_key' => ['nullable', 'string', 'max:191'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
