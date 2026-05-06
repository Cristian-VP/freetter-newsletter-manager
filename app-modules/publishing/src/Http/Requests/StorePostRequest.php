<?php

namespace Domains\Publishing\Http\Requests;

use Domains\Identity\Models\Membership;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $postId = $this->input('post_id');
        if (is_string($postId) && $postId !== '') {
            $post = Post::query()->find($postId);

            if (! $post) {
                return false;
            }

            return Membership::query()
                ->where('workspace_id', $post->workspace_id)
                ->where('user_id', $user->id)
                ->whereIn('role', ['owner', 'admin', 'editor', 'writer'])
                ->exists();
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
            'post_id' => ['nullable', 'uuid', 'exists:publishing_posts,id'],
            'author_id' => ['nullable', 'uuid', 'exists:identity_users,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:newsletter,note'],
            'content' => ['required'],
            'excerpt' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled', 'after:now'],
            'publish_now' => ['nullable', 'boolean'],
            'media' => ['nullable', 'array', 'max:8'],
            'media.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ];
    }
}
