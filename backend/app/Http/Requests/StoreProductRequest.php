<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitize inputs before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->input('code'))),
            ]);
        }

        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'code'           => ['required', 'string', 'max:50', 'unique:products,code'],
            'price'          => ['required', 'numeric', 'min:0.01'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'stock'          => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Product name is required.',
            'code.required'           => 'Product SKU code is required.',
            'code.unique'             => 'A product with this SKU code already exists.',
            'price.required'          => 'Product price is required.',
            'price.min'               => 'Price must be at least 0.01.',
            'tax_percentage.required' => 'Tax percentage is required.',
            'tax_percentage.min'      => 'Tax percentage cannot be negative.',
            'tax_percentage.max'      => 'Tax percentage cannot exceed 100%.',
            'stock.required'          => 'Initial stock quantity is required.',
            'stock.min'               => 'Stock quantity cannot be negative.',
        ];
    }
}
