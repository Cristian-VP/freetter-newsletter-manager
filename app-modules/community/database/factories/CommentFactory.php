<?php

namespace Domains\Community\Database\Factories;

use Domains\Community\Models\Comment;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'workspace_id' => Workspace::factory(),
            'parent_id' => null,
            'content' => $this->faker->sentence(12),
            'is_hidden' => false,
            'moderated_by_user_id' => null,
            'moderated_at' => null,
            'moderation_reason' => null,
        ];
    }

    public function reply(Comment $parent): static
    {
        return $this->state([
            'parent_id' => $parent->id,
            'post_id' => $parent->post_id,
            'workspace_id' => $parent->workspace_id,
        ]);
    }

    public function hidden(?User $moderator = null): static
    {
        return $this->state([
            'is_hidden' => true,
            'moderated_by_user_id' => $moderator?->id ?? User::factory(),
            'moderated_at' => now(),
            'moderation_reason' => 'Hidden by moderator',
        ]);
    }
}
