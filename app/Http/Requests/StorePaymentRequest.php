<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api') !== null;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::in(['fake', 'wallet'])],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
