<?php

namespace App\Domains\Group\Models;

use App\Domains\Chat\Models\Conversation;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Group extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'owner_id',
        'name',
        'slug',
        'description',
        'avatar',
        'type',
        'max_members',
        'is_verified',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'settings'    => 'array',
            'max_members' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($group) {
            if (!$group->slug) {
                $group->slug = Str::slug($group->name) . '-' . Str::random(6);
            }
        });
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }

    public function activeMembers()
    {
        return $this->hasMany(GroupMember::class)
            ->whereHas('user', fn($q) => $q->where('is_active', true)->whereNull('deleted_at'));
    }

    public function admins()
    {
        return $this->hasMany(GroupMember::class)
            ->whereIn('role', ['owner', 'admin']);
    }

    public function invitations()
    {
        return $this->hasMany(GroupInvitation::class);
    }

    public function joinRequests()
    {
        return $this->hasMany(GroupJoinRequest::class);
    }

    public function hasMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function getMemberRole(int $userId): ?string
    {
        return $this->members()->where('user_id', $userId)->value('role');
    }

    public function isAdmin(int $userId): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function isModerator(int $userId): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->whereIn('role', ['owner', 'admin', 'moderator'])
            ->exists();
    }

    public function getMembersCount(): int
    {
        return $this->members()->count();
    }

    public function isFull(): bool
    {
        return $this->getMembersCount() >= $this->max_members;
    }

    public function getDefaultSettings(): array
    {
        return [
            'who_can_send_messages' => 'members',
            'who_can_add_members'   => 'admins',
            'who_can_edit_info'     => 'admins',
            'join_approval'         => $this->type === 'private',
            'show_member_list'      => true,
        ];
    }

    public function getSetting(string $key): mixed
    {
        $settings = $this->settings ?? $this->getDefaultSettings();
        return $settings[$key] ?? $this->getDefaultSettings()[$key] ?? null;
    }
}