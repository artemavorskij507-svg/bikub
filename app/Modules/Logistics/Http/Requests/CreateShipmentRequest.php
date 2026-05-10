<?php

namespace App\Modules\Logistics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateShipmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'delivery_order_id' => ['nullable', 'integer', 'exists:delivery_orders,id'],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'pricing_rule_id' => ['nullable', 'integer', 'exists:pricing_rules,id'],
            'sender_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'recipient_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'origin_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'destination_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'customer_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'assigned_personnel_id' => ['nullable', 'integer', 'exists:delivery_personnel,id'],
            'priority' => ['nullable', 'string', 'max:32'],
            'promised_delivery_at' => ['nullable', 'date'],
            'scheduled_pickup_at' => ['nullable', 'date'],
            'scheduled_delivery_at' => ['nullable', 'date', 'after_or_equal:scheduled_pickup_at'],
            'external_reference' => ['nullable', 'string', 'max:80'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
            'parcel_count' => ['nullable', 'integer', 'min:1'],
            'total_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'total_volume_m3' => ['nullable', 'numeric', 'min:0'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $customerAddressId = $this->input('customer_address_id');

        $this->merge([
            'origin_address_id' => $this->input('origin_address_id', $customerAddressId),
            'destination_address_id' => $this->input('destination_address_id', $customerAddressId),
            'promised_delivery_at' => $this->input('promised_delivery_at', $this->input('scheduled_delivery_at')),
        ]);
    }
}

