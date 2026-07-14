<?php

namespace App\Domains\Presence\Events;

use App\Domains\User\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPresenceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public string $status
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('presence'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.presence';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'         => $this->user->id,
            'username'        => $this->user->username,
            'presence_status' => $this->status,
            'last_seen_at'    => $this->user->last_seen_at?->toISOString(),
        ];
    }
}