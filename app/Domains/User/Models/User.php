<?php

namespace App\Domains\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class User extends Authenticatable implements Auditable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles, AuditableTrait;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'bio',
        'custom_status',
        'presence_status',
        'last_seen_at',
        'phone',
        'timezone',
        'locale',
        'is_active',
        'privacy_settings',
        'notification_preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'         => 'datetime',
            'last_seen_at'              => 'datetime',
            'banned_at'                 => 'datetime',
            'two_factor_confirmed_at'   => 'datetime',
            'two_factor_secret'         => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'privacy_settings'          => 'array',
            'notification_preferences'  => 'array',
            'is_active'                 => 'boolean',
            'is_banned'                 => 'boolean',
        ];
    }

    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocker_id', 'blocked_id')->withTimestamps();
    }

    public function blockedByUsers()
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocked_id', 'blocker_id')->withTimestamps();
    }

    public function mutedUsers()
    {
        return $this->belongsToMany(User::class, 'user_mutes', 'muter_id', 'muted_id')->withTimestamps()->withPivot('expires_at');
    }

    public function isBlocking(User $user): bool
    {
        return $this->blockedUsers()->where('blocked_id', $user->id)->exists();
    }

    public function isMuting(User $user): bool
    {
        return $this->mutedUsers()->where('muted_id', $user->id)->exists();
    }

    public function isOnline(): bool
    {
        return $this->presence_status === 'online';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) return null;
        return asset('storage/' . $this->avatar);
    }
}