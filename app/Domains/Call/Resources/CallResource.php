<?php

namespace App\Domains\Call\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer      = $request->user();
        $myParticipant = $this->participants->firstWhere('user_id', $viewer?->id);

        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'type'            => $this->type,
            'status'          => $this->status,
            'duration'        => $this->duration,
            'duration_formatted' => $this->getDurationFormatted(),
            'started_at'      => $this->started_at?->toISOString(),
            'ended_at'        => $this->ended_at?->toISOString(),
            'created_at'      => $this->created_at->toISOString(),
            'initiator'       => $this->initiator ? [
                'id'     => $this->initiator->id,
                'name'   => $this->initiator->name,
                'avatar' => $this->initiator->avatar_url,
            ] : null,
            'participants'    => $this->whenLoaded('participants', fn() =>
                $this->participants->map(fn($p) => [
                    'user_id'   => $p->user_id,
                    'name'      => $p->user?->name,
                    'avatar'    => $p->user?->avatar_url,
                    'status'    => $p->status,
                    'is_muted'  => $p->is_muted,
                    'camera_on' => $p->camera_on,
                    'joined_at' => $p->joined_at?->toISOString(),
                    'left_at'   => $p->left_at?->toISOString(),
                ])
            ),
            'my_status'       => $myParticipant?->status,
            'is_muted'        => $myParticipant?->is_muted ?? false,
            'camera_on'       => $myParticipant?->camera_on ?? false,
            'am_initiator'    => $this->initiated_by === $viewer?->id,
        ];
    }
}