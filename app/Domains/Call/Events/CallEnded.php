<?php

namespace App\Domains\Call\Events;

use App\Domains\Call\Models\Call;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Call $call) {}

    public function broadcastOn(): array
    {
        $participantIds = $this->call->participants()->pluck('user_id')->toArray();
        return array_map(fn($id) => new PrivateChannel('user.' . $id), $participantIds);
    }

    public function broadcastAs(): string
    {
        return 'call.ended';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id'   => $this->call->id,
            'duration'  => $this->call->duration,
            'ended_at'  => $this->call->ended_at?->toISOString(),
            'status'    => $this->call->status,
        ];
    }
}