<?php

namespace App\Http\Requests\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class QuickOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $type = $this->input('type');

        $rules = [
            'type' => 'required|in:grocery,bulky,food',
            'address' => 'required|string|max:500',
        ];

        // Type-specific rules
        switch ($type) {
            case 'grocery':
                $rules['items'] = 'required|array|min:1';
                $rules['items.*.product_id'] = 'nullable|integer|exists:products,id';
                $rules['items.*.id'] = 'nullable|integer|exists:products,id';
                $rules['items.*.quantity'] = 'required|integer|min:1';
                $rules['items.*.unit_price'] = 'nullable|numeric|min:0';
                $rules['substitution_policy'] = 'nullable|in:strict,ai,contact';
                $rules['store_id'] = 'nullable|integer|exists:retail_stores,id';
                $rules['delivery_location'] = 'nullable|array';
                $rules['delivery_location.lat'] = 'nullable|numeric';
                $rules['delivery_location.lng'] = 'nullable|numeric';
                $rules['delivery_location.address'] = 'nullable|string';
                $rules['pickup_location'] = 'nullable|array';
                $rules['pickup_location.lat'] = 'nullable|numeric';
                $rules['pickup_location.lng'] = 'nullable|numeric';
                $rules['pickup_location.address'] = 'nullable|string';
                break;

            case 'bulky':
                $rules['dimensions'] = 'required|array';
                $rules['dimensions.length'] = 'required|numeric|min:1';
                $rules['dimensions.width'] = 'required|numeric|min:1';
                $rules['dimensions.height'] = 'required|numeric|min:1';
                $rules['weight_kg'] = 'nullable|numeric|min:0';
                $rules['services'] = 'nullable|array';
                $rules['services.*'] = 'in:assembly,disassembly,packaging,wrapping';
                $rules['floor_number'] = 'nullable|integer|min:0';
                $rules['elevator_available'] = 'nullable|boolean';
                break;

            case 'food':
                $rules['items'] = 'required|array|min:1';
                $rules['items.*.name'] = 'required|string';
                $rules['items.*.quantity'] = 'required|integer|min:1';
                $rules['items.*.price'] = 'required|numeric|min:0';
                $rules['restaurant_id'] = 'nullable|integer|exists:restaurants,id';
                $rules['special_instructions'] = 'nullable|string|max:1000';
                break;
        }

        return $rules;
    }
}
