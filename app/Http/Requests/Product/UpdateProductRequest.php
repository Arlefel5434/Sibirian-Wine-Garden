<?php

namespace App\Http\Requests\Product;

use App\Models\Sku;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) === true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'skus' => ['required', 'array', 'min:1'],
            'skus.*.id' => [
                'sometimes',
                'integer',
                Rule::exists('skus', 'id')->where('product_id', $product->id),
                'distinct',
            ],
            'skus.*.sku' => ['required', 'string', 'max:255', 'distinct'],
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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->input('skus', []) as $index => $sku) {
                    $conflict = Sku::query()
                        ->where('sku', $sku['sku'] ?? null)
                        ->when(isset($sku['id']), fn ($query) => $query->whereKeyNot($sku['id']))
                        ->exists();

                    if ($conflict) {
                        $validator->errors()->add("skus.$index.sku", 'The SKU has already been taken.');
                    }
                }
            },
        ];
    }
}
