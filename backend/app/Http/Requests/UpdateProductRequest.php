<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $product = $this->route('product');
        $productId = is_object($product) ? $product->id : null;

        return [
            'name'           => ['sometimes', 'required', 'string', 'max:255'],
            'code'           => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')->ignore($productId),
            ],
            'price'          => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'tax_percentage' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'stock'          => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Product name cannot be empty.',
            'code.required'           => 'Product SKU code cannot be empty.',
            'code.unique'             => 'A product with this SKU code already exists.',
            'price.required'          => 'Product price cannot be empty.',
            'price.min'               => 'Price must be at least 0.01.',
            'tax_percentage.min'      => 'Tax percentage cannot be negative.',
            'tax_percentage.max'      => 'Tax percentage cannot exceed 100%.',
            'stock.min'               => 'Stock quantity cannot be negative.',
        ];
    }
}
