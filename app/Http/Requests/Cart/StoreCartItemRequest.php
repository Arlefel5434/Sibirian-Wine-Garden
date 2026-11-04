<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api') !== null;
    }

    public function rules(): array
    {
        return ['sku_id' => ['required', 'integer', 'exists:skus,id'], 'quantity' => ['required', 'integer', 'min:1', 'max:99']];
    }
}
