<?php

namespace App\Http\Requests\Ops;

use App\Domain\Operations\Models\Executor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExecutorAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Executor|null $executor */
        $executor = $this->route('executor');

        return $executor && ($this->user()?->can('updateAvailability', $executor) ?? false);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:offline,available,busy,paused,suspended'],
            'availability_mode' => ['nullable', 'in:manual,shift_based,auto'],
        ];
    }
}

