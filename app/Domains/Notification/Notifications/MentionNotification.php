<?php

namespace App\Domains\Notification\Notifications;

use App\Domains\Chat\Models\Message;
use App\Domains\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Message $message,
        public User $mentionedBy
    ) {}

    public function via(object $notifiable): array
    {
        $prefs = $notifiable->notification_preferences ?? [];
        $channels = ['database'];
        if ($prefs['push'] ?? true)    $channels[] = 'broadcast';
        if ($prefs['mentions'] ?? true) $channels[] = 'mail';
        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'mention',
            'message_id'      => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'mentioned_by_id' => $this->mentionedBy->id,
            'mentioned_by'    => $this->mentionedBy->name,
            'avatar'          => $this->mentionedBy->avatar_url,
            'preview'         => mb_substr($this->message->body ?? '', 0, 100),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type'         => 'mention',
            'message_id'   => $this->message->id,
            'mentioned_by' => $this->mentionedBy->name,
            'preview'      => mb_substr($this->message->body ?? '', 0, 100),
            'created_at'   => now()->toISOString(),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->mentionedBy->name . ' ذكرك في رسالة')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line($this->mentionedBy->name . ' ذكرك في رسالة:')
            ->line('"' . mb_substr($this->message->body ?? '', 0, 200) . '"')
            ->action('عرض الرسالة', url('/chat'))
            ->line('شكراً لاستخدامك NexusPlatform');
    }
}