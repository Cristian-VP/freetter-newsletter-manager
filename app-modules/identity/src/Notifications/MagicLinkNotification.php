<?php

namespace Domains\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $magicLink) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu enlace magico de acceso a Freetter')
            ->greeting('Hola!')
            ->line('Usa este enlace para entrar a tu cuenta de Freetter.')
            ->line('Este enlace expirara en 30 minutos.')
            ->action('Entrar a Freetter', $this->magicLink)
            ->line('Si no solicitaste este acceso, puedes ignorar este correo.');
    }
}
