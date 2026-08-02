<?php

namespace App\Domains\Call\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class CallParticipant extends Model
{
    protected $fillable = [
        'call_id',
        'user_id',
        'status',
        'joined_at',
        'left_at',
        'is_muted',
        'camera_on',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at'   => 'datetime',
            'is_muted'  => 'boolean',
            'camera_on' => 'boolean',
        ];
    }

    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDuration(): int
    {
        if (!$this->joined_at || !$this->left_at) return 0;
        return $this->left_at->diffInSeconds($this->joined_at);
    }
}