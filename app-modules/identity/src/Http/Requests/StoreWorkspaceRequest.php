<?php

namespace Domains\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceRequest extends FormRequest
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
            'owner_user_id' => ['required', 'uuid', 'exists:identity_users,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:63', 'unique:identity_workspaces,slug'],
            'branding_config' => ['nullable', 'array'],
            'donation_config' => ['nullable', 'array'],
        ];
    }
}
