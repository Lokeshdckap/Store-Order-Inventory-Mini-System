<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitize input before validation rules are applied.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('customer_email')) {
            $this->merge([
                'customer_email' => strtolower(trim((string) $this->input('customer_email'))),
            ]);
        }

        if ($this->has('customer_name')) {
            $this->merge([
                'customer_name' => trim((string) $this->input('customer_name')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'customer_name'          => ['required', 'string', 'max:255'],
            'customer_email'         => ['required', 'string', 'email:filter', 'max:255'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_uuid'   => ['required', 'uuid', 'exists:products,uuid'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required'           => 'Customer name is required.',
            'customer_email.required'          => 'Customer email is required.',
            'customer_email.email'             => 'Please provide a valid customer email address.',
            'items.required'                   => 'Order must contain at least one item.',
            'items.min'                        => 'Order must contain at least one item.',
            'items.*.product_uuid.required'    => 'Product UUID is required for each line item.',
            'items.*.product_uuid.uuid'        => 'Product UUID must be a valid UUID.',
            'items.*.product_uuid.exists'      => 'Selected product does not exist in our catalog.',
            'items.*.quantity.required'        => 'Quantity is required for each line item.',
            'items.*.quantity.min'             => 'Quantity must be at least 1.',
        ];
    }
}
