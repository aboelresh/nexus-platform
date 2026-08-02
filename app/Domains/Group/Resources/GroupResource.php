<?php

namespace App\Domains\Group\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $member = $viewer ? $this->members->firstWhere('user_id', $viewer->id) : null;

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'avatar'         => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'type'           => $this->type,
            'is_verified'    => $this->is_verified,
            'members_count'  => $this->members->count(),
            'max_members'    => $this->max_members,
            'is_full'        => $this->isFull(),
            'settings'       => $this->settings ?? $this->getDefaultSettings(),
            'conversation_id'=> $this->conversation_id,
            'owner'          => $this->owner ? [
                'id'       => $this->owner->id,
                'name'     => $this->owner->name,
                'username' => $this->owner->username,
            ] : null,
            'my_role'        => $member?->role,
            'my_permissions' => $member ? [
                'can_send_messages' => !$member->isMuted() && !$member->isBanned(),
                'can_manage_messages' => $member->canManageMessages(),
                'can_manage_members'  => $member->canManageMembers(),
                'can_manage_settings' => $member->canManageSettings(),
            ] : null,
            'members' => $this->whenLoaded('members', fn() =>
    GroupMemberResource::collection($this->members)
),
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}