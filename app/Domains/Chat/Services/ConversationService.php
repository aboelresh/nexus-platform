<?php

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\ConversationParticipant;
use App\Domains\User\Models\User;
use Illuminate\Validation\ValidationException;

class ConversationService
{
    public function getUserConversations(User $user)
    {
        return Conversation::whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->with([
                'participants.user',
                'lastMessage.sender',
            ])
            ->orderByDesc('last_message_at')
            ->paginate(20);
    }

    public function createDirect(User $creator, int $targetUserId): Conversation
    {
        if ($creator->id === $targetUserId) {
            throw ValidationException::withMessages([
                'user_id' => ['لا يمكنك بدء محادثة مع نفسك.'],
            ]);
        }

        $target = User::findOrFail($targetUserId);

        if ($creator->isBlocking($target) || $target->isBlocking($creator)) {
            throw ValidationException::withMessages([
                'user_id' => ['لا يمكنك بدء محادثة مع هذا المستخدم.'],
            ]);
        }

        $existing = Conversation::where('type', 'direct')
            ->whereHas('participants', fn($q) => $q->where('user_id', $creator->id))
            ->whereHas('participants', fn($q) => $q->where('user_id', $targetUserId))
            ->first();

        if ($existing) return $existing;

        $conversation = Conversation::create([
            'type'       => 'direct',
            'created_by' => $creator->id,
        ]);

        $this->addParticipants($conversation, [
            ['user_id' => $creator->id, 'role' => 'member'],
            ['user_id' => $targetUserId, 'role' => 'member'],
        ]);

        return $conversation->load('participants.user');
    }

    public function createGroup(User $creator, array $data): Conversation
    {
        $conversation = Conversation::create([
            'type'       => 'group',
            'name'       => $data['name'],
            'description'=> $data['description'] ?? null,
            'created_by' => $creator->id,
        ]);

        $participants = [['user_id' => $creator->id, 'role' => 'owner']];

        foreach ($data['members'] ?? [] as $memberId) {
            if ($memberId !== $creator->id) {
                $participants[] = ['user_id' => $memberId, 'role' => 'member'];
            }
        }

        $this->addParticipants($conversation, $participants);

        return $conversation->load('participants.user');
    }

    public function getConversation(int $conversationId, User $user): Conversation
    {
        $conversation = Conversation::with([
            'participants.user',
            'lastMessage.sender',
        ])->findOrFail($conversationId);

        if (!$conversation->hasParticipant($user->id)) {
            throw ValidationException::withMessages([
                'conversation' => ['ليس لديك صلاحية الوصول لهذه المحادثة.'],
            ]);
        }

        return $conversation;
    }

    public function markAsRead(Conversation $conversation, User $user): void
    {
        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }

    public function archiveConversation(Conversation $conversation, User $user): void
    {
        $this->ensureParticipant($conversation, $user);
        $conversation->update(['is_archived' => true]);
    }

    private function addParticipants(Conversation $conversation, array $participants): void
    {
        foreach ($participants as $participant) {
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id'         => $participant['user_id'],
                'role'            => $participant['role'],
                'joined_at'       => now(),
            ]);
        }
    }

    private function ensureParticipant(Conversation $conversation, User $user): void
    {
        if (!$conversation->hasParticipant($user->id)) {
            throw ValidationException::withMessages([
                'conversation' => ['ليس لديك صلاحية للوصول لهذه المحادثة.'],
            ]);
        }
    }
}