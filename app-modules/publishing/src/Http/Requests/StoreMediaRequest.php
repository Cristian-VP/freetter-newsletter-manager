<?php

declare(strict_types=1);

namespace Domains\Publishing\Http\Requests;

use Domains\Identity\Models\Membership;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $workspaceId = $this->input('workspace_id');

        if (! is_string($workspaceId) || $workspaceId === '') {
            $workspaceId = Membership::query()
                ->where('user_id', (string) $user->id)
                ->orderByDesc('joined_at')
                ->value('workspace_id');

            if (! is_string($workspaceId) || $workspaceId === '') {
                return false;
            }
        }

        $canAccessWorkspaceMedia = Membership::query()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', (string) $user->id)
            ->exists();

        return $canAccessWorkspaceMedia;
    }

    /**
     * Get the workspace ID for this request.
     */
    public function workspaceId(): string
    {
        $workspaceId = $this->input('workspace_id');

        if (is_string($workspaceId) && $workspaceId !== '') {
            return $workspaceId;
        }

        $user = $this->user();

        if (! $user) {
            return '';
        }

        $derivedWorkspaceId = Membership::query()
            ->where('user_id', (string) $user->id)
            ->orderByDesc('joined_at')
            ->value('workspace_id');

        return is_string($derivedWorkspaceId) ? $derivedWorkspaceId : '';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
            'workspace_id' => ['sometimes', 'string'],
        ];
    }
}
