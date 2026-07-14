<?php

namespace App\Domains\Chat\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'is_muted',
        'muted_until',
        'last_read_at',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'is_muted'    => 'boolean',
            'muted_until' => 'datetime',
            'last_read_at'=> 'datetime',
            'joined_at'   => 'datetime',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
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
}