<?php

namespace App\Domains\Chat\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\User\Resources\UserResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer      = $request->user();
        $participant = null;

        if ($viewer && $this->relationLoaded('participants')) {
            $participant = $this->participants
                ->firstWhere('user_id', $viewer->id);
        }

        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'name'            => $this->getNameForViewer($viewer),
            'avatar'          => $this->getAvatarForViewer($viewer),
            'is_archived'     => $this->is_archived,
            'last_message_at' => $this->last_message_at?->toISOString(),
            'last_message'    => $this->whenLoaded('lastMessage', fn() =>
                new MessageResource($this->lastMessage)
            ),
            'participants'    => $this->whenLoaded('participants', fn() =>
                $this->participants->map(fn($p) => [
                    'user_id'  => $p->user_id,
                    'role'     => $p->role,
                    'name'     => $this->relationLoaded('participants') && $p->relationLoaded('user')
                        ? $p->user?->name
                        : null,
                    'avatar'   => $this->relationLoaded('participants') && $p->relationLoaded('user')
                        ? $p->user?->avatar_url
                        : null,
                    'is_muted' => $p->isMuted(),
                ])
            ),
            'my_role'         => $participant?->role,
            'is_muted'        => $participant?->isMuted() ?? false,
            'unread_count'    => 0,
            'created_at'      => $this->created_at->toISOString(),
        ];
    }

    private function getNameForViewer($viewer): ?string
    {
        if ($this->type !== 'direct') return $this->name;
        if (!$viewer) return null;
        if (!$this->relationLoaded('participants')) return null;

        $other = $this->participants
            ->firstWhere('user_id', '!=', $viewer->id);

        if (!$other || !$other->relationLoaded('user')) return null;

        return $other->user?->name;
    }

    private function getAvatarForViewer($viewer): ?string
    {
        if ($this->type !== 'direct') return $this->avatar;
        if (!$viewer) return null;
        if (!$this->relationLoaded('participants')) return null;

        $other = $this->participants
            ->firstWhere('user_id', '!=', $viewer->id);

        if (!$other || !$other->relationLoaded('user')) return null;

        return $other->user?->avatar_url;
    }
}