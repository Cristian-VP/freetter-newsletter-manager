<?php

declare(strict_types=1);

namespace Domains\Community\Database\Seeders;

use Domains\Community\Models\Follower;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Media;
use Domains\Publishing\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SubscriptionsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $viewer = $this->firstOrCreateUser('subscriptions.viewer@demo.freetter.test', 'Marina Vega');

        $workspaceOne = Workspace::query()->updateOrCreate(
            ['slug' => 'subscriptions-demo-studio'],
            [
                'name' => 'Subscriptions Demo Studio',
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

        $workspaceTwo = Workspace::query()->updateOrCreate(
            ['slug' => 'subscriptions-demo-letters'],
            [
                'name' => 'Subscriptions Demo Letters',
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

        Follower::query()->firstOrCreate([
            'follower_id' => $viewer->id,
            'followed_workspace_id' => $workspaceOne->id,
        ]);

        Follower::query()->firstOrCreate([
            'follower_id' => $viewer->id,
            'followed_workspace_id' => $workspaceTwo->id,
        ]);

        $authorOne = $this->firstOrCreateUser('subscriptions.author.one@demo.freetter.test', 'Rigoberta Bandini');
        $authorTwo = $this->firstOrCreateUser('subscriptions.author.two@demo.freetter.test', 'Luna Ortega');
        $authorThree = $this->firstOrCreateUser('subscriptions.author.three@demo.freetter.test', 'Carmen Pacheco');

        $this->seedNewsletter(
            workspace: $workspaceOne,
            author: $authorOne,
            title: 'Carta de amor a la rutina visible',
            slug: 'carta-de-amor-a-la-rutina-visible',
            excerpt: '',
            publishedAt: now()->subHours(4),
            paragraphs: [
                'Una newsletter diaria no necesita prometer épica; basta con ordenar bien lo que ya estaba disperso.',
                'La clave está en un inicio claro, una lectura rápida en móvil y una imagen que acompañe sin competir.',
            ],
            mediaUrls: [
                'https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=1600&q=80',
            ]
        );

        $this->seedNewsletter(
            workspace: $workspaceTwo,
            author: $authorTwo,
            title: 'Cuando una newsletter no lleva imagen',
            slug: 'cuando-una-newsletter-no-lleva-imagen',
            excerpt: 'Este ejemplo sirve para validar el espacio en blanco cuando no hay media adjunta.',
            publishedAt: now()->subDay(),
            paragraphs: [
                'Algunas piezas funcionan mejor sin imagen, dejando que el bloque de texto marque el ritmo visual.',
                'En mobile el diseño sigue respirando porque el hueco reservado evita saltos en la composición.',
            ],
            mediaUrls: []
        );

        $this->seedNewsletter(
            workspace: $workspaceOne,
            author: $authorThree,
            title: 'Tres notas para leer despacio',
            slug: 'tres-notas-para-leer-despacio',
            excerpt: '',
            publishedAt: now()->subDays(2),
            paragraphs: [
                'Primera nota: una portada pequeña y cuadrada funciona mejor en mobile que una imagen recortada al azar.',
                'Segunda nota: el cuerpo abierto debe sentirse como una hoja completa, no como un panel secundario.',
                'Tercera nota: la jerarquía tipográfica tiene que sostener el contenido sin añadir ruido extra.',
            ],
            mediaUrls: [
                'https://images.unsplash.com/photo-1522542550221-31fd19575a2d?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=1600&q=80',
            ]
        );
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

    /**
     * @param  array<int, string>  $paragraphs
     * @param  array<int, string>  $mediaUrls
     */
    private function seedNewsletter(
        Workspace $workspace,
        User $author,
        string $title,
        string $slug,
        string $excerpt,
        Carbon $publishedAt,
        array $paragraphs,
        array $mediaUrls,
    ): void {
        /** @var Post $post */
        $post = Post::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'slug' => $slug,
            ],
            [
                'author_id' => $author->id,
                'title' => $title,
                'type' => 'newsletter',
                'status' => 'published',
                'content' => [
                    'blocks' => array_map(
                        static fn (string $paragraph): array => [
                            'type' => 'paragraph',
                            'data' => [
                                'text' => $paragraph,
                            ],
                        ],
                        $paragraphs,
                    ),
                ],
                'excerpt' => $excerpt,
                'carbon_score' => 0,
                'published_at' => $publishedAt,
            ]
        );

        foreach ($mediaUrls as $mediaUrl) {
            /** @var Media $media */
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

            $post->media()->syncWithoutDetaching([$media->id]);
        }
    }
}
