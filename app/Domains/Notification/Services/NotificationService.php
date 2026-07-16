<?php

namespace App\Domains\Notification\Services;

use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Group\Models\Group;
use App\Domains\Group\Models\GroupInvitation;
use App\Domains\Notification\Notifications\GroupInvitationNotification;
use App\Domains\Notification\Notifications\JoinRequestNotification;
use App\Domains\Notification\Notifications\MentionNotification;
use App\Domains\Notification\Notifications\NewMessageNotification;
use App\Domains\Notification\Notifications\SystemNotification;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function notifyNewMessage(Message $message, User $sender): void
    {
        $conversation = $message->conversation;

        $participants = $conversation->participants()
            ->where('user_id', '!=', $sender->id)
            ->with('user')
            ->get();

        foreach ($participants as $participant) {
            $user = $participant->user;
            if (!$user) continue;

            $prefs = $user->notification_preferences ?? [];
            if (!($prefs['messages'] ?? true)) continue;

            if ($sender->isBlocking($user) || $user->isBlocking($sender)) continue;

            $user->notify(new NewMessageNotification($message, $sender));
        }
    }

    public function notifyMentions(Message $message, User $sender, array $mentionedUserIds): void
    {
        $users = User::whereIn('id', $mentionedUserIds)
            ->where('id', '!=', $sender->id)
            ->get();

        foreach ($users as $user) {
            $prefs = $user->notification_preferences ?? [];
            if (!($prefs['mentions'] ?? true)) continue;

            $user->notify(new MentionNotification($message, $sender));
        }
    }

    public function notifyGroupInvitation(GroupInvitation $invitation, Group $group, User $invitedBy): void
    {
        $invitedUser = User::find($invitation->invited_user_id);
        if (!$invitedUser) return;

        $invitedUser->notify(new GroupInvitationNotification($invitation, $group, $invitedBy));
    }

    public function notifyJoinRequest(Group $group, User $requester): void
    {
        $admins = User::whereHas('groupMembers', fn($q) =>
            $q->where('group_id', $group->id)->whereIn('role', ['owner', 'admin'])
        )->get();

        foreach ($admins as $admin) {
            $admin->notify(new JoinRequestNotification($group, $requester));
        }
    }

    public function notifySystem(User $user, string $title, string $body, string $type = 'info', ?string $actionUrl = null, ?string $actionText = null): void
    {
        $user->notify(new SystemNotification($title, $body, $type, $actionUrl, $actionText));
    }

    public function notifyAll(string $title, string $body, string $type = 'info'): void
    {
        User::where('is_active', true)->chunk(100, function ($users) use ($title, $body, $type) {
            foreach ($users as $user) {
                $user->notify(new SystemNotification($title, $body, $type));
            }
        });
    }

    public function getUserNotifications(User $user, bool $unreadOnly = false, int $perPage = 20)
    {
        $query = $user->notifications();

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return $query->latest()->paginate($perPage);
    }

    public function markAsRead(User $user, ?string $notificationId = null): void
    {
        if ($notificationId) {
            $user->notifications()->where('id', $notificationId)->update(['read_at' => now()]);
        } else {
            $user->unreadNotifications->markAsRead();
        }
    }

    public function deleteNotification(User $user, string $notificationId): void
    {
        $user->notifications()->where('id', $notificationId)->delete();
    }

    public function deleteAllNotifications(User $user): void
    {
        $user->notifications()->delete();
    }

    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function getNotificationStats(User $user): array
    {
        return [
            'total'   => $user->notifications()->count(),
            'unread'  => $user->unreadNotifications()->count(),
            'by_type' => DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', User::class)
                ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) as type, COUNT(*) as count")
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }
}