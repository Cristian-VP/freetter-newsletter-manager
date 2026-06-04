<?php

namespace Domains\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginMagicLinkRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255', 'exists:identity_users,email'],
        ];
    }
}
