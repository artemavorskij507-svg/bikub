<?php

namespace App\Http\Requests\Api\Delivery;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryQuoteRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in(['grocery', 'bulky', 'food']),
            ],

            'pickup_address' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],

            'pickup_location' => ['nullable', 'array'],
            'pickup_location.lat' => ['nullable', 'numeric'],
            'pickup_location.lng' => ['nullable', 'numeric'],
            'pickup_location.address' => ['nullable', 'string', 'max:255'],

            'delivery_location' => ['nullable', 'array'],
            'delivery_location.lat' => ['nullable', 'numeric'],
            'delivery_location.lng' => ['nullable', 'numeric'],
            'delivery_location.address' => ['nullable', 'string', 'max:255'],

            // Grocery
            'store_id' => ['required_if:type,grocery', 'integer', 'exists:retail_stores,id'],
            'items' => ['required_if:type,grocery', 'array'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'substitution_policy' => [
                'nullable',
                Rule::in(['strict', 'ai', 'contact']),
            ],

            // Bulky
            'dimensions' => ['nullable', 'array'],
            'dimensions.length' => ['required_if:type,bulky', 'numeric', 'min:1'],
            'dimensions.width' => ['required_if:type,bulky', 'numeric', 'min:1'],
            'dimensions.height' => ['required_if:type,bulky', 'numeric', 'min:1'],
            'dimensions.length_cm' => ['nullable', 'numeric', 'min:1'],
            'dimensions.width_cm' => ['nullable', 'numeric', 'min:1'],
            'dimensions.height_cm' => ['nullable', 'numeric', 'min:1'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'max:64'],
            'floor_number' => ['nullable', 'integer', 'min:0'],
            'elevator_available' => ['nullable', 'boolean'],

            // Food
            'restaurant_id' => ['required_if:type,food', 'integer', 'exists:restaurants,id'],
            'food_items' => ['required_if:type,food', 'array'],
            'food_items.*.menu_item_id' => ['nullable', 'integer'],
            'food_items.*.id' => ['nullable', 'integer'],
            'food_items.*.quantity' => ['required_with:food_items', 'integer', 'min:1'],
            'food_items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function validatedPayload(): array
    {
        $data = $this->validated();

        if (! isset($data['delivery_address']) && isset($data['address'])) {
            $data['delivery_address'] = $data['address'];
        }

        // Normalize bulky dimensions if provided as *_cm
        if (isset($data['dimensions'])) {
            foreach (['length', 'width', 'height'] as $key) {
                $cmKey = "{$key}_cm";
                if (! isset($data['dimensions'][$key]) && isset($data['dimensions'][$cmKey])) {
                    $data['dimensions'][$key] = $data['dimensions'][$cmKey];
                }
            }
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = collect($data['items'])->map(function ($item) {
                if (! isset($item['product_id']) && isset($item['id'])) {
                    $item['product_id'] = $item['id'];
                }

                return $item;
            })->toArray();
        }

        if (isset($data['food_items']) && is_array($data['food_items'])) {
            $data['food_items'] = collect($data['food_items'])->map(function ($item) {
                if (! isset($item['menu_item_id']) && isset($item['id'])) {
                    $item['menu_item_id'] = $item['id'];
                }

                return $item;
            })->toArray();
        }

        return $data;
    }
}
