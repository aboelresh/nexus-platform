<?php

namespace App\Domains\Group\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GroupInvitation extends Model
{
    protected $fillable = [
        'group_id',
        'invited_by',
        'invited_user_id',
        'token',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($inv) {
            if (!$inv->token) {
                $inv->token = Str::random(32);
            }
            if (!$inv->expires_at) {
                $inv->expires_at = now()->addDays(7);
            }
        });
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function invitedUser()
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }
}