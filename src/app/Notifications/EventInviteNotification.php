<?php

namespace App\Notifications;

use App\Models\EventInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public EventInvite $invite,
        public ?string $inviteLink = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Invitation à un événement')
            ->greeting('Salut !')
            ->line("Tu as été invité à l'événement : {$this->invite->event->title}");

        if ($this->inviteLink) {
            $mail->action('Rejoindre l’événement', $this->inviteLink)
                ->line("Ce lien expire le {$this->invite->expires_at?->toDateTimeString()}.");
        } else {
            $mail->line("Connecte-toi à l'app pour accepter ou refuser l’invitation.");
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
