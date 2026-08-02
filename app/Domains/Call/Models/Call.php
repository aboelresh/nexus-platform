<?php

namespace App\Domains\Call\Models;

use App\Domains\Chat\Models\Conversation;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    protected $fillable = [
        'conversation_id',
        'initiated_by',
        'type',
        'status',
        'started_at',
        'ended_at',
        'duration',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at'   => 'datetime',
            'duration'   => 'integer',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function participants()
    {
        return $this->hasMany(CallParticipant::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['ringing', 'ongoing']);
    }

    public function getDurationFormatted(): string
    {
        if (!$this->duration) return '00:00';
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}