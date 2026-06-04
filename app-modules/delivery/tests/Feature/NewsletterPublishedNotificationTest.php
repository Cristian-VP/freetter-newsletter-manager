<?php

namespace Domains\Delivery\Tests\Feature;

use Domains\Delivery\Notifications\NewsletterPublishedNotification;
use Domains\Identity\Models\User;
use Domains\Identity\Models\Workspace;
use Domains\Publishing\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterPublishedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_renders_the_newsletter_body_and_uses_the_configured_sender(): void
    {
        config()->set('mail.from.address', 'no-reply@freetter.app');
        config()->set('mail.from.name', 'Freetter');

        $workspace = Workspace::factory()->create([
            'name' => 'Cris Studio',
        ]);

        $author = User::factory()->create([
            'name' => 'Cris',
            'email' => 'cris@freetter.app',
        ]);

        $post = Post::factory()->newsletter()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'title' => 'Weekly Update',
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'heading',
                        'attrs' => ['level' => 1],
                        'content' => [
                            ['type' => 'text', 'text' => 'Hello subscribers'],
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'This is the '],
                            [
                                'type' => 'text',
                                'text' => 'newsletter',
                                'marks' => [['type' => 'bold']],
                            ],
                            ['type' => 'text', 'text' => ' content.'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'attrs' => [
                            'src' => 'https://example.com/newsletter-image.jpg',
                            'alt' => 'Cover image',
                            'title' => 'Cover image',
                        ],
                    ],
                ],
            ],
        ]);

        $notification = new NewsletterPublishedNotification($post);
        $mailMessage = $notification->toMail($author);

        $this->assertSame(['no-reply@freetter.app', 'Freetter'], $mailMessage->from);
        $this->assertSame([['cris@freetter.app', 'Cris']], $mailMessage->replyTo);
        $this->assertSame('emails.newsletter-published', $mailMessage->view);
        $this->assertSame('Cris Studio', $mailMessage->viewData['workspaceName']);
        $this->assertSame('Cris', $mailMessage->viewData['authorName']);
        $this->assertSame($notification->renderNewsletterHtml(), $mailMessage->viewData['newsletterHtml']);
        $this->assertStringContainsString('<h1>Hello subscribers</h1>', $mailMessage->viewData['newsletterHtml']);
        $this->assertStringContainsString('<strong>newsletter</strong>', $mailMessage->viewData['newsletterHtml']);
        $this->assertStringContainsString('newsletter-image.jpg', $mailMessage->viewData['newsletterHtml']);
    }

    public function test_render_email_html_inlines_css(): void
    {
        $workspace = Workspace::factory()->create([
            'name' => 'Test Workspace',
        ]);

        $author = User::factory()->create([
            'name' => 'Test Author',
        ]);

        $post = Post::factory()->newsletter()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'title' => 'CSS Inline Test',
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'heading',
                        'attrs' => ['level' => 1],
                        'content' => [
                            ['type' => 'text', 'text' => 'Heading'],
                        ],
                    ],
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'Paragraph text.'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'attrs' => [
                            'src' => 'https://example.com/image.jpg',
                            'alt' => 'Test image',
                            'title' => 'Test image',
                        ],
                    ],
                ],
            ],
        ]);

        $notification = new NewsletterPublishedNotification($post);
        $html = $notification->renderEmailHtml();

        $this->assertMatchesRegularExpression('/^<!doctype html>/i', $html);
        $this->assertStringContainsString('style=', $html);
        $this->assertStringContainsString('@media', $html);
        $this->assertStringContainsString('https://example.com/image.jpg', $html);
    }

    public function test_render_email_html_strips_base64_images(): void
    {
        $workspace = Workspace::factory()->create([
            'name' => 'Test Workspace',
        ]);

        $author = User::factory()->create([
            'name' => 'Test Author',
        ]);

        $post = Post::factory()->newsletter()->create([
            'workspace_id' => $workspace->id,
            'author_id' => $author->id,
            'title' => 'Base64 Test',
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'Hello world'],
                        ],
                    ],
                    [
                        'type' => 'image',
                        'attrs' => [
                            'src' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
                            'alt' => 'Base64 image',
                            'title' => 'Base64 image',
                        ],
                    ],
                ],
            ],
        ]);

        $notification = new NewsletterPublishedNotification($post);
        $html = $notification->renderEmailHtml();

        $this->assertStringNotContainsString('data:image/png;base64', $html);
    }
}
