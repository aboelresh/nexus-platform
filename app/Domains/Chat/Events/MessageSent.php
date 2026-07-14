<?php

namespace App\Domains\Chat\Events;

use App\Domains\Chat\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $message = $this->message->load(['sender', 'replyTo.sender', 'reactions', 'reads', 'mentions']);

        return [
            'message' => [
                'id'              => $message->id,
                'conversation_id' => $message->conversation_id,
                'type'            => $message->type,
                'body'            => $message->body,
                'is_edited'       => (bool) $message->is_edited,
                'is_pinned'       => (bool) $message->is_pinned,
                'is_deleted'      => !is_null($message->deleted_at),
                'sender'          => $message->sender ? [
                    'id'       => $message->sender->id,
                    'name'     => $message->sender->name,
                    'username' => $message->sender->username,
                    'avatar'   => $message->sender->avatar_url,
                ] : null,
                'reply_to'        => $message->replyTo ? [
                    'id'   => $message->replyTo->id,
                    'body' => $message->replyTo->body,
                ] : null,
                'reactions'       => $message->reactions
                    ->groupBy('emoji')
                    ->map(fn($group, $emoji) => [
                        'emoji' => $emoji,
                        'count' => $group->count(),
                        'users' => $group->pluck('user_id'),
                    ])->values()->toArray(),
                'reads_count'     => $message->reads->count(),
                'mentions'        => $message->mentions->pluck('user_id')->toArray(),
                'created_at'      => $message->created_at->toISOString(),
            ],
        ];
    }
}