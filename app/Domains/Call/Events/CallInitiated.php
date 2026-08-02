<?php

namespace App\Domains\Call\Events;

use App\Domains\Call\Models\Call;
use App\Domains\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallInitiated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Call $call,
        public User $caller,
        public array $targetUserIds
    ) {}

    public function broadcastOn(): array
    {
        return array_map(
            fn($userId) => new PrivateChannel('user.' . $userId),
            $this->targetUserIds
        );
    }

    public function broadcastAs(): string
    {
        return 'call.initiated';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id'         => $this->call->id,
            'type'            => $this->call->type,
            'conversation_id' => $this->call->conversation_id,
            'caller'          => [
                'id'     => $this->caller->id,
                'name'   => $this->caller->name,
                'avatar' => $this->caller->avatar_url,
            ],
            'created_at' => now()->toISOString(),
        ];
    }
}