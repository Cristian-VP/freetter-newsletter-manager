<?php

namespace Domains\Community\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModerateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:hide,delete'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
