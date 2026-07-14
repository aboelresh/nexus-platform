<?php

namespace App\Domains\Chat\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\User\Resources\UserResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        $reactions = $this->whenLoaded('reactions', function () {
            return $this->reactions
                ->groupBy('emoji')
                ->map(fn($group, $emoji) => [
                    'emoji' => $emoji,
                    'count' => $group->count(),
                    'users' => $group->pluck('user_id'),
                ])->values();
        });

        return [
            'id'               => $this->id,
            'conversation_id'  => $this->conversation_id,
            'type'             => $this->type,
            'body'             => $this->body,
            'is_edited'        => $this->is_edited,
            'edited_at'        => $this->edited_at?->toISOString(),
            'is_pinned'        => $this->is_pinned,
            'is_deleted'       => !is_null($this->deleted_at),
            'sender'           => new UserResource($this->whenLoaded('sender')),
            'reply_to'         => new MessageResource($this->whenLoaded('replyTo')),
            'reactions'        => $reactions,
            'reads_count'      => $this->whenLoaded('reads', fn() => $this->reads->count()),
            'is_read'          => $viewer ? $this->isReadBy($viewer->id) : false,
            'mentions'         => $this->whenLoaded('mentions', fn() => $this->mentions->pluck('user_id')),
            'created_at'       => $this->created_at->toISOString(),
        ];
    }
}