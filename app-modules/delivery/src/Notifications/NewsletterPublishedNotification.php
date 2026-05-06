<?php

namespace Domains\Delivery\Notifications;

use Domains\Publishing\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewsletterPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Post $post) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspaceName = $this->post->workspace?->name ?? 'Freetter';

        return (new MailMessage)
            ->subject($workspaceName.': '.$this->post->title)
            ->greeting('Hola!')
            ->line('Se ha publicado una nueva newsletter en Freetter.')
            ->line($this->post->excerpt ?: $this->post->getExcerpt(240))
            ->action('Ver newsletter', route('home').'#post-'.$this->post->id)
            ->line('Si prefieres leerla en la web, puedes abrirla desde el enlace anterior.');
    }
}
