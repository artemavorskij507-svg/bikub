<?php

namespace App\Modules\Logistics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignRouteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'shipment_id' => ['required','integer','exists:shipments,id'],
            'delivery_route_id' => ['required','integer','exists:delivery_routes,id'],
            'delivery_personnel_id' => ['nullable','integer','exists:delivery_personnel,id'],
        ];
    }
}
