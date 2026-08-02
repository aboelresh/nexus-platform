<?php

namespace App\Domains\Call\Events;

use App\Domains\Call\Models\Call;
use App\Domains\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallAnswered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Call $call,
        public User $answeredBy
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->call->initiated_by)];
    }

    public function broadcastAs(): string
    {
        return 'call.answered';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id'     => $this->call->id,
            'answered_by' => [
                'id'   => $this->answeredBy->id,
                'name' => $this->answeredBy->name,
            ],
            'started_at' => $this->call->started_at?->toISOString(),
        ];
    }
}