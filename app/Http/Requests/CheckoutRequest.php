<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api') !== null;
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
            'city' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:24'],
            'address_line' => ['required', 'string', 'max:255'],
        ];
    }
}
