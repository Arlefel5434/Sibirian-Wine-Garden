<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateAdministration', $this->route('user')) === true;
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'required', Rule::in(['admin', 'content_manager', 'customer'])],
            'banned' => ['sometimes', 'required', 'boolean'],
            'ban_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->has('role') && ! $this->has('banned')) {
                    $validator->errors()->add('user', 'A role or ban status must be provided.');
                }
            },
        ];
    }
}
