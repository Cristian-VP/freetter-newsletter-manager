<?php

namespace Domains\Publishing\Http\Requests;

use Domains\Identity\Models\Membership;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $workspaceId = (string) $this->route('workspace');

        return Membership::query()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'editor', 'writer'])
            ->exists();
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
