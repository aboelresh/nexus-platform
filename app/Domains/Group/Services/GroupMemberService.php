<?php

namespace App\Domains\Group\Services;

use App\Domains\Chat\Models\ConversationParticipant;
use App\Domains\Group\Models\Group;
use App\Domains\Group\Models\GroupJoinRequest;
use App\Domains\Group\Models\GroupMember;
use App\Domains\User\Models\User;
use Illuminate\Validation\ValidationException;

class GroupMemberService
{
    public function getMembers(Group $group, User $viewer)
    {
        if (!$group->hasMember($viewer->id) && $group->type !== 'public') {
            throw ValidationException::withMessages([
                'group' => ['ليس لديك صلاحية عرض أعضاء هذه المجموعة.'],
            ]);
        }

        return $group->members()
            ->with(['user', 'invitedBy'])
            ->orderByRaw("FIELD(role, 'owner', 'admin', 'moderator', 'member')")
            ->paginate(50);
    }

    public function changeRole(Group $group, User $admin, int $targetUserId, string $newRole): GroupMember
    {
        if (!$group->isAdmin($admin->id)) {
            throw ValidationException::withMessages([
                'group' => ['ليس لديك صلاحية تغيير الأدوار.'],
            ]);
        }

        $targetMember = GroupMember::where('group_id', $group->id)
            ->where('user_id', $targetUserId)
            ->firstOrFail();

        if ($targetMember->isOwner()) {
            throw ValidationException::withMessages([
                'group' => ['لا يمكن تغيير دور المالك.'],
            ]);
        }

        if ($newRole === 'owner') {
            throw ValidationException::withMessages([
                'group' => ['لنقل الملكية استخدم Endpoint نقل الملكية.'],
            ]);
        }

        $adminMember = GroupMember::where('group_id', $group->id)->where('user_id', $admin->id)->first();
        if ($adminMember->role === 'admin' && $targetMember->role === 'admin' && $admin->id !== $group->owner_id) {
            throw ValidationException::withMessages([
                'group' => ['Admin لا يمكنه تغيير دور Admin آخر.'],
            ]);
        }

        $targetMember->update(['role' => $newRole]);

        ConversationParticipant::where('conversation_id', $group->conversation_id)
            ->where('user_id', $targetUserId)
            ->update(['role' => $newRole]);

        return $targetMember->fresh('user');
    }

    public function kickMember(Group $group, User $admin, int $targetUserId): void
    {
        if (!$group->isModerator($admin->id)) {
            throw ValidationException::withMessages([
                'group' => ['ليس لديك صلاحية طرد الأعضاء.'],
            ]);
        }

        $targetMember = GroupMember::where('group_id', $group->id)
            ->where('user_id', $targetUserId)
            ->firstOrFail();

        if ($targetMember->isOwner()) {
            throw ValidationException::withMessages([
                'group' => ['لا يمكن طرد مالك المجموعة.'],
            ]);
        }

        $adminMember = GroupMember::where('group_id', $group->id)->where('user_id', $admin->id)->first();
        if ($targetMember->isAdmin() && !$adminMember->isOwner()) {
            throw ValidationException::withMessages([
                'group' => ['المشرف لا يمكنه طرد Admin. فقط المالك يمكنه ذلك.'],
            ]);
        }

        $targetMember->delete();

        ConversationParticipant::where('conversation_id', $group->conversation_id)
            ->where('user_id', $targetUserId)
            ->delete();
    }

    public function banMember(Group $group, User $admin, int $targetUserId, ?string $reason = null): void
    {
        if (!$group->isModerator($admin->id)) {
            throw ValidationException::withMessages([
                'group' => ['ليس لديك صلاحية حظر الأعضاء.'],
            ]);
        }

        $targetMember = GroupMember::where('group_id', $group->id)
            ->where('user_id', $targetUserId)
            ->firstOrFail();

        if ($targetMember->isOwner()) {
            throw ValidationException::withMessages([
                'group' => ['لا يمكن حظر مالك المجموعة.'],
            ]);
        }

        $targetMember->update([
            'banned_at'  => now(),
            'ban_reason' => $reason,
        ]);
    }

    public function unbanMember(Group $group, User $admin, int $targetUserId): void
    {
        if (!$group->isModerator($admin->id)) {
            throw ValidationException::withMessages([
                'group' => ['ليس لديك صلاحية.'],
            ]);
        }

        GroupMember::where('group_id', $group->id)
            ->where('user_id', $targetUserId)
            ->update(['banned_at' => null, 'ban_reason' => null]);
    }

    public function muteMember(Group $group, User $admin, int $targetUserId, ?int $minutes = null): void
    {
        if (!$group->isModerator($admin->id)) {
            throw ValidationException::withMessages([
                'group' => ['ليس لديك صلاحية كتم الأعضاء.'],
            ]);
        }

        $member = GroupMember::where('group_id', $group->id)
            ->where('user_id', $targetUserId)
            ->firstOrFail();

        if ($member->isOwner()) {
            throw ValidationException::withMessages([
                'group' => ['لا يمكن كتم مالك المجموعة.'],
            ]);
        }

        $member->update([
            'is_muted'    => true,
            'muted_until' => $minutes ? now()->addMinutes($minutes) : null,
        ]);
    }

    public function unmuteMember(Group $group, User $admin, int $targetUserId): void
    {
        if (!$group->isModerator($admin->id)) {
            throw ValidationException::withMessages([
                'group' => ['ليس لديك صلاحية.'],
            ]);
        }

        GroupMember::where('group_id', $group->id)
            ->where('user_id', $targetUserId)
            ->update(['is_muted' => false, 'muted_until' => null]);
    }

    public function getJoinRequests(Group $group, User $admin)
    {
        if (!$group->isAdmin($admin->id)) {
            throw ValidationException::withMessages([
                'group' => ['ليس لديك صلاحية عرض طلبات الانضمام.'],
            ]);
        }

        return GroupJoinRequest::where('group_id', $group->id)
            ->where('status', 'pending')
            ->with('user')
            ->latest()
            ->paginate(20);
    }

    public function reviewJoinRequest(Group $group, User $admin, int $requestId, bool $approve): GroupJoinRequest
    {
        if (!$group->isAdmin($admin->id)) {
            throw ValidationException::withMessages([
                'group' => ['ليس لديك صلاحية مراجعة الطلبات.'],
            ]);
        }

        $request = GroupJoinRequest::where('group_id', $group->id)
            ->where('id', $requestId)
            ->where('status', 'pending')
            ->firstOrFail();

        $request->update([
            'status'      => $approve ? 'approved' : 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        if ($approve) {
            GroupMember::firstOrCreate(
                ['group_id' => $group->id, 'user_id' => $request->user_id],
                ['role' => 'member', 'joined_at' => now()]
            );

            ConversationParticipant::firstOrCreate(
                ['conversation_id' => $group->conversation_id, 'user_id' => $request->user_id],
                ['role' => 'member', 'joined_at' => now()]
            );
        }

        return $request->fresh('user');
    }
}