<?php

namespace Domains\Community\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFollowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'followed_workspace_id' => ['required', 'uuid', 'exists:identity_workspaces,id'],
        ];
    }
}
