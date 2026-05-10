<?php

namespace App\Modules\Logistics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrackingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'event_type' => ['required','string','max:64'],
            'status' => ['required','string','max:64'],
            'message' => ['nullable','string','max:2000'],
            'latitude' => ['nullable','numeric','between:-90,90'],
            'longitude' => ['nullable','numeric','between:-180,180'],
            'happened_at' => ['nullable','date'],
        ];
    }
}
