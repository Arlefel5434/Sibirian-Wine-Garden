<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'categories' => ['nullable', 'array', 'max:20'],
            'categories.*' => ['integer', 'distinct', 'exists:categories,id'],
            'price_from' => ['nullable', 'integer', 'min:0'],
            'price_to' => ['nullable', 'integer', 'min:0', 'gte:price_from'],
            'in_stock' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['draft', 'active', 'archived'])],
            'sort' => ['nullable', Rule::in(['newest', 'name_asc', 'price_asc', 'price_desc'])],
            'attributes' => ['nullable', 'array', 'max:20'],
            'attributes.*' => ['array', 'max:30'],
            'attributes.*.*' => ['string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }
}
