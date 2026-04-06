<?php

namespace Domains\Audience\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImportJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'created_by_user_id' => ['required', 'uuid', 'exists:identity_users,id'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ];
    }
}
