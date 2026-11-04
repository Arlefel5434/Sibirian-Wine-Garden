<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('api') !== null;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user('api')->id)],
            'phone_number' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s().-]{5,30}$/'],
        ];
    }
}
