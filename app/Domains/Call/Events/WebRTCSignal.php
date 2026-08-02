<?php

namespace App\Domains\Call\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebRTCSignal implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $callId,
        public int    $fromUserId,
        public int    $toUserId,
        public string $signalType,
        public array  $payload
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->toUserId)];
    }

    public function broadcastAs(): string
    {
        return 'webrtc.signal';
    }

    public function broadcastWith(): array
    {
        return [
            'call_id'     => $this->callId,
            'from_user_id'=> $this->fromUserId,
            'signal_type' => $this->signalType,
            'payload'     => $this->payload,
        ];
    }

    public function shouldQueue(): bool
    {
        return false;
    }
}