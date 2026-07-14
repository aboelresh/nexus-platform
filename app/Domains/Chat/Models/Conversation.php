<?php

namespace App\Domains\Chat\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'description',
        'avatar',
        'created_by',
        'is_archived',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'is_archived'     => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function activeParticipants()
    {
        return $this->hasMany(ConversationParticipant::class)
                    ->whereHas('user', fn($q) => $q->where('is_active', true)->whereNull('deleted_at'));
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    public function isDirectBetween(int $userId1, int $userId2): bool
    {
        if ($this->type !== 'direct') return false;

        return $this->participants()
                    ->whereIn('user_id', [$userId1, $userId2])
                    ->count() === 2;
    }
}