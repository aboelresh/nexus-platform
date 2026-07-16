<?php

namespace App\Domains\Notification\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public string $type = 'info',
        public ?string $actionUrl = null,
        public ?string $actionText = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'system',
            'sub_type'    => $this->type,
            'title'       => $this->title,
            'body'        => $this->body,
            'action_url'  => $this->actionUrl,
            'action_text' => $this->actionText,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type'       => 'system',
            'sub_type'   => $this->type,
            'title'      => $this->title,
            'body'       => $this->body,
            'created_at' => now()->toISOString(),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->body);

        if ($this->actionUrl && $this->actionText) {
            $mail->action($this->actionText, $this->actionUrl);
        }

        return $mail->line('شكراً لاستخدامك NexusPlatform');
    }
}