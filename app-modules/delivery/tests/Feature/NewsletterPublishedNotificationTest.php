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
}
