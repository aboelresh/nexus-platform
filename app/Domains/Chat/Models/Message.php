<?php

namespace App\Domains\Chat\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'reply_to_id',
        'forwarded_from_id',
        'type',
        'body',
        'is_edited',
        'edited_at',
        'is_pinned',
        'pinned_at',
        'pinned_by',
    ];

    protected function casts(): array
    {
        return [
            'is_edited' => 'boolean',
            'is_pinned' => 'boolean',
            'edited_at' => 'datetime',
            'pinned_at' => 'datetime',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    public function forwardedFrom()
    {
        return $this->belongsTo(Message::class, 'forwarded_from_id');
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function reads()
    {
        return $this->hasMany(MessageRead::class);
    }

    public function mentions()
    {
        return $this->hasMany(MessageMention::class);
    }

    public function media()
    {
        return $this->hasMany(\App\Domains\Media\Models\Media::class);
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    public function isReadBy(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }
}