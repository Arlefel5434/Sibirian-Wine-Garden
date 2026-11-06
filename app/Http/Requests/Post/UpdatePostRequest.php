<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('post')) === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'preview_img' => ['nullable', 'url:http,https', 'max:2048'],
            'preview_text' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'body' => ['required', 'array', 'min:1'],
            'body.*.type' => ['required', 'string', Rule::in(['heading', 'paragraph', 'image'])],
            'body.*.data' => ['required', 'array'],
            'translations' => ['sometimes', 'array'],
            'translations.*.locale' => ['required', 'string', 'distinct', Rule::in(array_keys(config('localization.supported')))],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.preview_text' => ['nullable', 'string', 'max:500'],
            'translations.*.body' => ['required', 'array', 'min:1'],
            'translations.*.body.*.type' => ['required', 'string', Rule::in(['heading', 'paragraph', 'image'])],
            'translations.*.body.*.data' => ['required', 'array'],
        ];
    }
}
