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
        $participant = $this->participants->firstWhere('user_id', $viewer?->id);

        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'name'            => $this->getNameForViewer($viewer),
            'avatar'          => $this->getAvatarForViewer($viewer),
            'is_archived'     => $this->is_archived,
            'last_message_at' => $this->last_message_at?->toISOString(),
            'last_message'    => new MessageResource($this->whenLoaded('lastMessage')),
            'participants'    => UserResource::collection($this->whenLoaded('activeParticipants')),
            'my_role'         => $participant?->role,
            'is_muted'        => $participant?->isMuted() ?? false,
            'unread_count'    => $this->whenLoaded('messages', function () use ($participant) {
                if (!$participant?->last_read_at) return $this->messages->count();
                return $this->messages->where('created_at', '>', $participant->last_read_at)->count();
            }),
            'created_at'      => $this->created_at->toISOString(),
        ];
    }

    private function getNameForViewer($viewer): ?string
    {
        if ($this->type !== 'direct') return $this->name;
        if (!$viewer) return null;

        $otherParticipant = $this->participants
            ->where('user_id', '!=', $viewer->id)
            ->first();

        return $otherParticipant?->user?->name;
    }

    private function getAvatarForViewer($viewer): ?string
    {
        if ($this->type !== 'direct') return $this->avatar;
        if (!$viewer) return null;

        $otherParticipant = $this->participants
            ->where('user_id', '!=', $viewer->id)
            ->first();

        return $otherParticipant?->user?->avatar_url;
    }
}