<?php

namespace Domains\Publishing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'author_id' => ['required', 'uuid', 'exists:identity_users,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:newsletter,note'],
            'content' => ['required', 'array'],
            'excerpt' => ['nullable', 'string'],
        ];
    }
}
