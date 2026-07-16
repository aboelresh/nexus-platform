<?php

namespace App\Domains\Group\Services;

use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\ConversationParticipant;
use App\Domains\Group\Models\Group;
use App\Domains\Group\Models\GroupJoinRequest;
use App\Domains\Group\Models\GroupMember;
use App\Domains\User\Models\User;
use Illuminate\Validation\ValidationException;

class GroupService
{
    public function getUserGroups(User $user)
    {
        return Group::whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->with(['owner', 'members'])
            ->withCount('members')
            ->latest()
            ->paginate(20);
    }

    public function create(User $creator, array $data): Group
    {
        $conversation = Conversation::create([
            'type'       => 'group',
            'name'       => $data['name'],
            'description'=> $data['description'] ?? null,
            'created_by' => $creator->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id'         => $creator->id,
            'role'            => 'owner',
            'joined_at'       => now(),
        ]);

        $group = Group::create([
            'conversation_id' => $conversation->id,
            'owner_id'        => $creator->id,
            'name'            => $data['name'],
            'description'     => $data['description'] ?? null,
            'type'            => $data['type'] ?? 'public',
            'max_members'     => $data['max_members'] ?? 1000,
        ]);

        GroupMember::create([
            'group_id'  => $group->id,
            'user_id'   => $creator->id,
            'role'      => 'owner',
            'joined_at' => now(),
        ]);

        foreach ($data['members'] ?? [] as $memberId) {
            if ($memberId !== $creator->id) {
                $this->addMember($group, $memberId, $creator->id);
            }
        }

        return $group->load(['owner', 'members.user', 'members.invitedBy', 'activeMembers.user']);
    }

    public function update(Group $group, User $user, array $data): Group
    {
        if (!$group->isAdmin($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['ليس لديك صلاحية تعديل هذه المجموعة.'],
            ]);
        }

        if (isset($data['settings'])) {
            $currentSettings = $group->settings ?? $group->getDefaultSettings();
            $data['settings'] = array_merge($currentSettings, $data['settings']);
        }

        $group->update($data);

        if (isset($data['name'])) {
            $group->conversation->update(['name' => $data['name']]);
        }

        return $group->fresh(['owner', 'members.user']);
    }

    public function delete(Group $group, User $user): void
    {
        if ($group->owner_id !== $user->id) {
            throw ValidationException::withMessages([
                'group' => ['فقط مالك المجموعة يمكنه حذفها.'],
            ]);
        }

        $group->conversation->delete();
        $group->delete();
    }

    public function getGroup(int $groupId, User $user): Group
    {
        $group = Group::with(['owner', 'members.user', 'activeMembers.user'])->findOrFail($groupId);

        if ($group->type !== 'public' && !$group->hasMember($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['ليس لديك صلاحية الوصول لهذه المجموعة.'],
            ]);
        }

        return $group;
    }

    public function join(Group $group, User $user): void
    {
        if ($group->hasMember($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['أنت بالفعل عضو في هذه المجموعة.'],
            ]);
        }

        if ($group->type === 'invite_only') {
            throw ValidationException::withMessages([
                'group' => ['هذه المجموعة بالدعوة فقط.'],
            ]);
        }

        if ($group->isFull()) {
            throw ValidationException::withMessages([
                'group' => ['المجموعة وصلت الحد الأقصى من الأعضاء.'],
            ]);
        }

        if ($group->type === 'private' || $group->getSetting('join_approval')) {
            $existing = GroupJoinRequest::where('group_id', $group->id)
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'group' => ['طلبك قيد المراجعة بالفعل.'],
                ]);
            }

            GroupJoinRequest::create([
                'group_id' => $group->id,
                'user_id'  => $user->id,
                'status'   => 'pending',
            ]);

            return;
        }

        $this->addMember($group, $user->id);
    }

    public function leave(Group $group, User $user): void
    {
        if (!$group->hasMember($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['أنت لست عضواً في هذه المجموعة.'],
            ]);
        }

        if ($group->owner_id === $user->id) {
            throw ValidationException::withMessages([
                'group' => ['مالك المجموعة لا يمكنه المغادرة. يرجى نقل الملكية أولاً.'],
            ]);
        }

        GroupMember::where('group_id', $group->id)->where('user_id', $user->id)->delete();

        ConversationParticipant::where('conversation_id', $group->conversation_id)
            ->where('user_id', $user->id)
            ->delete();
    }

    public function transferOwnership(Group $group, User $currentOwner, int $newOwnerId): void
    {
        if ($group->owner_id !== $currentOwner->id) {
            throw ValidationException::withMessages([
                'group' => ['فقط المالك يمكنه نقل الملكية.'],
            ]);
        }

        if (!$group->hasMember($newOwnerId)) {
            throw ValidationException::withMessages([
                'group' => ['المستخدم المحدد ليس عضواً في المجموعة.'],
            ]);
        }

        GroupMember::where('group_id', $group->id)->where('user_id', $currentOwner->id)->update(['role' => 'admin']);
        GroupMember::where('group_id', $group->id)->where('user_id', $newOwnerId)->update(['role' => 'owner']);
        $group->update(['owner_id' => $newOwnerId]);
    }

    public function searchPublicGroups(string $query, int $perPage = 20)
    {
        return Group::where('type', 'public')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->with('owner')
            ->withCount('members')
            ->latest()
            ->paginate($perPage);
    }

    private function addMember(Group $group, int $userId, ?int $invitedBy = null): void
    {
        GroupMember::firstOrCreate(
            ['group_id' => $group->id, 'user_id' => $userId],
            ['role' => 'member', 'joined_at' => now(), 'invited_by' => $invitedBy]
        );

        ConversationParticipant::firstOrCreate(
            ['conversation_id' => $group->conversation_id, 'user_id' => $userId],
            ['role' => 'member', 'joined_at' => now()]
        );
    }
}