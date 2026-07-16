<?php

namespace App\Domains\Group\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'is_muted',
        'muted_until',
        'banned_at',
        'ban_reason',
        'joined_at',
        'invited_by',
    ];

    protected $table = 'group_members';

    protected function casts(): array
    {
        return [
            'is_muted'   => 'boolean',
            'muted_until'=> 'datetime',
            'banned_at'  => 'datetime',
            'joined_at'  => 'datetime',
        ];
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function isModerator(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'moderator']);
    }

    public function isBanned(): bool
    {
        return !is_null($this->banned_at);
    }

    public function isMuted(): bool
    {
        if (!$this->is_muted) return false;
        if ($this->muted_until && $this->muted_until->isPast()) {
            $this->update(['is_muted' => false, 'muted_until' => null]);
            return false;
        }
        return true;
    }

    public function canManageMessages(): bool
    {
        return $this->isModerator();
    }

    public function canManageMembers(): bool
    {
        return $this->isAdmin();
    }

    public function canManageSettings(): bool
    {
        return $this->isAdmin();
    }
}