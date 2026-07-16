<?php

namespace App\Domains\Notification\Notifications;

use App\Domains\Chat\Models\Message;
use App\Domains\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Message $message,
        public User $sender
    ) {}

    public function via(object $notifiable): array
    {
        $prefs = $notifiable->notification_preferences ?? [];

        $channels = ['database'];

        if ($prefs['push'] ?? true) {
            $channels[] = 'broadcast';
        }

        if ($prefs['email'] ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'new_message',
            'message_id'      => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id'       => $this->sender->id,
            'sender_name'     => $this->sender->name,
            'sender_avatar'   => $this->sender->avatar_url,
            'preview'         => mb_substr($this->message->body ?? '', 0, 100),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type'            => 'new_message',
            'message_id'      => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_name'     => $this->sender->name,
            'sender_avatar'   => $this->sender->avatar_url,
            'preview'         => mb_substr($this->message->body ?? '', 0, 100),
            'created_at'      => now()->toISOString(),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('رسالة جديدة من ' . $this->sender->name)
            ->greeting('مرحباً ' . $notifiable->name)
            ->line($this->sender->name . ' أرسل إليك رسالة:')
            ->line('"' . mb_substr($this->message->body ?? '', 0, 200) . '"')
            ->action('فتح المحادثة', url('/chat'))
            ->line('شكراً لاستخدامك NexusPlatform');
    }

    public function broadcastOn(): array
    {
        return ['private-user.' . $this->message->conversation->participants()
            ->where('user_id', '!=', $this->sender->id)
            ->pluck('user_id')
            ->first()];
    }
}