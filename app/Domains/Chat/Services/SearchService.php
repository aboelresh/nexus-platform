<?php

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Models\Message;
use App\Domains\Chat\Models\Conversation;
use App\Domains\User\Models\User;
use Illuminate\Validation\ValidationException;

class SearchService
{
    public function searchMessages(string $query, User $user, ?int $conversationId = null, int $perPage = 20)
    {
        $userConversationIds = Conversation::whereHas(
            'participants',
            fn($q) => $q->where('user_id', $user->id)
        )->pluck('id');

        $search = Message::with(['sender', 'conversation'])
            ->whereIn('conversation_id', $userConversationIds)
            ->whereNull('deleted_at')
            ->where('body', 'LIKE', "%{$query}%");

        if ($conversationId) {
            if (!in_array($conversationId, $userConversationIds->toArray())) {
                throw ValidationException::withMessages([
                    'conversation_id' => ['ليس لديك صلاحية الوصول لهذه المحادثة.'],
                ]);
            }
            $search->where('conversation_id', $conversationId);
        }

        return $search->latest()->paginate($perPage);
    }

    public function searchConversations(string $query, User $user, int $perPage = 20)
    {
        return Conversation::whereHas(
            'participants',
            fn($q) => $q->where('user_id', $user->id)
        )
        ->where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('description', 'LIKE', "%{$query}%");
        })
        ->with(['participants.user', 'lastMessage.sender'])
        ->latest()
        ->paginate($perPage);
    }
}