<?php

namespace App\Domains\Group\Services;

use App\Domains\Chat\Models\ConversationParticipant;
use App\Domains\Group\Models\Group;
use App\Domains\Group\Models\GroupInvitation;
use App\Domains\Group\Models\GroupMember;
use App\Domains\User\Models\User;
use Illuminate\Validation\ValidationException;

class GroupInvitationService
{
    public function invite(Group $group, User $inviter, int $targetUserId): GroupInvitation
    {
        if (!$group->hasMember($inviter->id)) {
            throw ValidationException::withMessages([
                'group' => ['يجب أن تكون عضواً في المجموعة لدعوة الآخرين.'],
            ]);
        }

        $canInvite = $group->getSetting('who_can_add_members');
        if ($canInvite === 'admins' && !$group->isAdmin($inviter->id)) {
            throw ValidationException::withMessages([
                'group' => ['فقط المشرفون يمكنهم دعوة الأعضاء.'],
            ]);
        }

        if ($group->hasMember($targetUserId)) {
            throw ValidationException::withMessages([
                'user_id' => ['المستخدم عضو بالفعل في المجموعة.'],
            ]);
        }

        if ($group->isFull()) {
            throw ValidationException::withMessages([
                'group' => ['المجموعة وصلت الحد الأقصى من الأعضاء.'],
            ]);
        }

        $existing = GroupInvitation::where('group_id', $group->id)
            ->where('invited_user_id', $targetUserId)
            ->where('status', 'pending')
            ->first();

        if ($existing && !$existing->isExpired()) {
            throw ValidationException::withMessages([
                'user_id' => ['تمت دعوة هذا المستخدم بالفعل وطلبه قيد الانتظار.'],
            ]);
        }

        return GroupInvitation::create([
            'group_id'        => $group->id,
            'invited_by'      => $inviter->id,
            'invited_user_id' => $targetUserId,
            'status'          => 'pending',
        ]);
    }

    public function acceptInvitation(User $user, string $token): Group
    {
        $invitation = GroupInvitation::where('token', $token)
            ->where('invited_user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            $invitation->update(['status' => 'expired']);
            throw ValidationException::withMessages([
                'token' => ['انتهت صلاحية هذه الدعوة.'],
            ]);
        }

        $group = $invitation->group;

        if ($group->isFull()) {
            throw ValidationException::withMessages([
                'group' => ['المجموعة ممتلئة.'],
            ]);
        }

        GroupMember::firstOrCreate(
            ['group_id' => $group->id, 'user_id' => $user->id],
            ['role' => 'member', 'joined_at' => now(), 'invited_by' => $invitation->invited_by]
        );

        ConversationParticipant::firstOrCreate(
            ['conversation_id' => $group->conversation_id, 'user_id' => $user->id],
            ['role' => 'member', 'joined_at' => now()]
        );

        $invitation->update(['status' => 'accepted']);

        return $group->load(['owner', 'members.user']);
    }

    public function declineInvitation(User $user, string $token): void
    {
        $invitation = GroupInvitation::where('token', $token)
            ->where('invited_user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $invitation->update(['status' => 'rejected']);
    }

    public function getUserInvitations(User $user)
    {
        return GroupInvitation::where('invited_user_id', $user->id)
            ->where('status', 'pending')
            ->with(['group.owner', 'invitedBy'])
            ->latest()
            ->paginate(20);
    }
}