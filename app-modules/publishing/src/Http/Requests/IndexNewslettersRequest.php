<?php

namespace Domains\Publishing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexNewslettersRequest extends FormRequest
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
            'workspace_id' => ['nullable', 'uuid'],
            'status' => ['nullable', 'in:all,draft,scheduled,published'],
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }
}
