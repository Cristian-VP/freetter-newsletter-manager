<?php

namespace Domains\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:63', 'unique:identity_workspaces,slug'],
            'branding_config' => ['nullable', 'array'],
            'donation_config' => ['nullable', 'array'],
        ];
    }
}
