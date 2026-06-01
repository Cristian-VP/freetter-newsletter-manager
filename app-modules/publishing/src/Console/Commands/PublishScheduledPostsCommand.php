<?php

namespace Domains\Publishing\Console\Commands;

use Domains\Publishing\Events\PostPublished;
use Domains\Publishing\Models\Post;
use Domains\Publishing\Models\PostVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublishScheduledPostsCommand extends Command
{
    protected $signature = 'publishing:publish-scheduled-posts';

    protected $description = 'Publish scheduled posts whose published_at is due.';

    public function handle(): int
    {
        $now = now();

        Post::query()
            ->where('status', 'scheduled')
            ->where('published_at', '<=', $now)
            ->orderBy('published_at')
            ->chunkById(100, function ($posts): void {
                foreach ($posts as $post) {
                    DB::transaction(function () use ($post): void {
                        $fresh = Post::query()->find($post->id);

                        if (! $fresh || $fresh->status !== 'scheduled') {
                            return;
                        }

                        if ($fresh->published_at === null || $fresh->published_at->isFuture()) {
                            return;
                        }

                        $fresh->update([
                            'status' => 'published',
                            // keep published_at as is (already UTC)
                        ]);

                        $nextVersionNumber = (int) PostVersion::query()
                            ->where('post_id', $fresh->id)
                            ->max('version_number') + 1;

                        PostVersion::query()->create([
                            'post_id' => $fresh->id,
                            'content' => $fresh->content,
                            'version_number' => $nextVersionNumber,
                            'created_at' => now(),
                        ]);

                        event(new PostPublished(
                            post: $fresh->fresh(),
                            publishedByUserId: null,
                            context: ['published_via' => 'scheduler'],
                        ));
                    });
                }
            });

        $this->info('Publishing scheduled posts job completed.');

        return self::SUCCESS;
    }
}
