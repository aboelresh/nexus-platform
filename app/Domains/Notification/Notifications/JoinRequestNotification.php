<?php

namespace App\Domains\Notification\Notifications;

use App\Domains\Group\Models\Group;
use App\Domains\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Group $group,
        public User $requester
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'join_request',
            'group_id'       => $this->group->id,
            'group_name'     => $this->group->name,
            'requester_id'   => $this->requester->id,
            'requester_name' => $this->requester->name,
            'avatar'         => $this->requester->avatar_url,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type'           => 'join_request',
            'group_name'     => $this->group->name,
            'requester_name' => $this->requester->name,
            'created_at'     => now()->toISOString(),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->requester->name . ' يطلب الانضمام إلى ' . $this->group->name)
            ->greeting('مرحباً ' . $notifiable->name)
            ->line($this->requester->name . ' يطلب الانضمام إلى مجموعة "' . $this->group->name . '"')
            ->action('مراجعة الطلب', url('/groups/' . $this->group->id . '/join-requests'))
            ->line('شكراً لاستخدامك NexusPlatform');
    }
}