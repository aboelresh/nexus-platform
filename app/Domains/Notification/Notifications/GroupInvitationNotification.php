<?php

namespace App\Domains\Notification\Notifications;

use App\Domains\Group\Models\Group;
use App\Domains\Group\Models\GroupInvitation;
use App\Domains\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public GroupInvitation $invitation,
        public Group $group,
        public User $invitedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'group_invitation',
            'group_id'    => $this->group->id,
            'group_name'  => $this->group->name,
            'invited_by'  => $this->invitedBy->name,
            'avatar'      => $this->invitedBy->avatar_url,
            'token'       => $this->invitation->token,
            'expires_at'  => $this->invitation->expires_at->toISOString(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type'       => 'group_invitation',
            'group_name' => $this->group->name,
            'invited_by' => $this->invitedBy->name,
            'token'      => $this->invitation->token,
            'created_at' => now()->toISOString(),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('دعوة للانضمام إلى ' . $this->group->name)
            ->greeting('مرحباً ' . $notifiable->name)
            ->line($this->invitedBy->name . ' يدعوك للانضمام إلى مجموعة "' . $this->group->name . '"')
            ->action('قبول الدعوة', url('/invitations/accept?token=' . $this->invitation->token))
            ->line('تنتهي صلاحية الدعوة في: ' . $this->invitation->expires_at->format('Y-m-d H:i'))
            ->line('شكراً لاستخدامك NexusPlatform');
    }
}