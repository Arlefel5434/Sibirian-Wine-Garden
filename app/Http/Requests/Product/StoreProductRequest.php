<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'skus' => ['required', 'array', 'min:1'],
            'skus.*.sku' => ['required', 'string', 'max:255', 'distinct', 'unique:skus,sku'],
            'skus.*.name' => ['required', 'string', 'max:255'],
            'skus.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'skus.*.price' => ['required', 'integer', 'min:0'],
            'skus.*.currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'skus.*.quantity_unit' => ['required', Rule::in(['piece', 'gram', 'millilitre'])],
            'attributes' => ['sometimes', 'array'],
            'attributes.*.attribute_id' => ['required', 'integer', 'distinct', 'exists:attributes,id'],
            'attributes.*.value' => ['required', 'string', 'max:255'],
        ];
    }
}
