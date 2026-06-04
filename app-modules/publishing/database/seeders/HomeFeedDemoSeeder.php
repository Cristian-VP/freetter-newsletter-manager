<?php

declare(strict_types=1);

namespace Domains\Publishing\Database\Seeders;

use Domains\Community\Models\Like;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Media;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HomeFeedDemoSeeder extends Seeder
{
    private ?string $postMediaPivotTable = null;

    public function run(): void
    {
        $workspace = Workspace::query()->firstOrCreate(
            ['slug' => 'global-feed-demo'],
            [
                'name' => 'Global Feed Demo',
                'branding_config' => [
                    'logo_url' => null,
                    'primary_color' => '#111111',
                    'secondary_color' => '#6b7280',
                ],
                'donation_config' => [
                    'default_amount' => 10,
                    'currency' => 'EUR',
                ],
            ]
        );

        $viewer = $this->firstOrCreateUser('viewer@demo.freetter.test', 'Feed Viewer');

        $authors = collect([
            ['email' => 'author.one@demo.freetter.test', 'name' => 'Lara Soto'],
            ['email' => 'author.two@demo.freetter.test', 'name' => 'Mateo Ruiz'],
            ['email' => 'author.three@demo.freetter.test', 'name' => 'Nora Vidal'],
        ])->map(fn (array $author): User => $this->firstOrCreateUser($author['email'], $author['name']));

        $likers = collect([
            $viewer,
            $this->firstOrCreateUser('reader.one@demo.freetter.test', 'Aitana Soler'),
            $this->firstOrCreateUser('reader.two@demo.freetter.test', 'Iker Pons'),
            $authors[0],
            $authors[1],
            $authors[2],
        ]);

        $postsData = [
            [
                'author' => $authors[0],
                'title' => 'Cinco ideas para escribir mejor cada dia',
                'excerpt' => 'Un sistema simple para convertir notas sueltas en posts claros sin bloquearte.',
                'published_at' => now()->subHours(2),
                'media_urls' => [
                    'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=1600&q=80',
                    'https://images.unsplash.com/photo-1488190211105-8b0e65b80b4e?auto=format&fit=crop&w=1600&q=80',
                ],
                'likes_from' => [
                    'viewer@demo.freetter.test',
                    'reader.one@demo.freetter.test',
                    'author.two@demo.freetter.test',
                ],
            ],
            [
                'author' => $authors[1],
                'title' => 'Como estructurar una nota corta que se lea completa',
                'excerpt' => 'Primera linea fuerte, conflicto claro y cierre accionable en menos de un minuto de lectura.',
                'published_at' => now()->subDay(),
                'media_urls' => [
                    'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=1600&q=80',
                ],
                'likes_from' => [
                    'reader.two@demo.freetter.test',
                    'author.three@demo.freetter.test',
                ],
            ],
            [
                'author' => $authors[2],
                'title' => 'Checklist editorial de diez minutos antes de publicar',
                'excerpt' => 'Revisa claridad, ritmo y promesa en una pasada rapida para subir calidad sin friccion.',
                'published_at' => now()->subDays(3),
                'media_urls' => [
                    'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=1600&q=80',
                    'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=1600&q=80',
                    'https://images.unsplash.com/photo-1529078155058-5d716f45d604?auto=format&fit=crop&w=1600&q=80',
                ],
                'likes_from' => [
                    'viewer@demo.freetter.test',
                    'reader.one@demo.freetter.test',
                    'reader.two@demo.freetter.test',
                    'author.one@demo.freetter.test',
                ],
            ],
        ];

        foreach ($postsData as $index => $item) {
            $slug = Str::slug($item['title']).'-demo-'.($index + 1);

            $post = Post::query()->updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'slug' => $slug,
                ],
                [
                    'author_id' => $item['author']->id,
                    'title' => $item['title'],
                    'type' => 'note',
                    'status' => 'published',
                    'content' => [
                        'blocks' => [[
                            'type' => 'paragraph',
                            'data' => [
                                'text' => $item['excerpt'],
                            ],
                        ]],
                    ],
                    'excerpt' => $item['excerpt'],
                    'carbon_score' => 0,
                    'published_at' => $item['published_at'],
                ]
            );

            foreach ($item['media_urls'] as $mediaUrl) {
                $media = Media::query()->firstOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'path' => $mediaUrl,
                    ],
                    [
                        'disk' => 'local',
                        'mime_type' => 'image/jpeg',
                        'size_kb' => 640,
                    ]
                );

                $this->attachMediaToPost((string) $post->id, (string) $media->id);
            }

            foreach ($item['likes_from'] as $email) {
                $user = $likers->first(fn (User $candidate): bool => $candidate->email === $email);

                if (! $user instanceof User) {
                    continue;
                }

                Like::query()->firstOrCreate([
                    'user_id' => $user->id,
                    'post_id' => $post->id,
                ]);
            }
        }
    }

    private function firstOrCreateUser(string $email, string $name): User
    {
        /** @var User $user */
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email_verified_at' => now(),
                'avatar_path' => null,
            ]
        );

        return $user;
    }

    private function attachMediaToPost(string $postId, string $mediaId): void
    {
        DB::table($this->resolvePostMediaPivotTable())->updateOrInsert(
            [
                'post_id' => $postId,
                'media_id' => $mediaId,
            ],
            []
        );
    }

    private function resolvePostMediaPivotTable(): string
    {
        if ($this->postMediaPivotTable !== null) {
            return $this->postMediaPivotTable;
        }

        if (Schema::hasTable('publishing_post_media')) {
            $this->postMediaPivotTable = 'publishing_post_media';

            return $this->postMediaPivotTable;
        }

        if (Schema::hasTable('publishing__post_media')) {
            $this->postMediaPivotTable = 'publishing__post_media';

            return $this->postMediaPivotTable;
        }

        throw new \RuntimeException('No pivot table found for post-media relation. Expected publishing_post_media or publishing__post_media.');
    }
}
