<?php

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Events\MessageDeleted;
use App\Domains\Chat\Events\MessageSent;
use App\Domains\Chat\Events\MessageUpdated;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Chat\Models\MessageRead;
use App\Domains\Chat\Models\MessageReaction;
use App\Domains\User\Models\User;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public function getMessages(Conversation $conversation, User $user, int $perPage = 30)
    {
        if (!$conversation->hasParticipant($user->id)) {
            throw ValidationException::withMessages([
                'conversation' => ['ليس لديك صلاحية الوصول لهذه المحادثة.'],
            ]);
        }

        return Message::where('conversation_id', $conversation->id)
    ->with(['sender', 'replyTo.sender', 'reactions.user', 'reads', 'mentions', 'media'])
    ->latest()
    ->paginate($perPage);
    }

    public function sendMessage(Conversation $conversation, User $sender, array $data): Message
    {
        if (!$conversation->hasParticipant($sender->id)) {
            throw ValidationException::withMessages([
                'conversation' => ['ليس لديك صلاحية إرسال رسائل في هذه المحادثة.'],
            ]);
        }

        if ($sender->is_banned) {
            throw ValidationException::withMessages([
                'sender' => ['حسابك محظور ولا يمكنك إرسال رسائل.'],
            ]);
        }

        $message = Message::create([
            'conversation_id'   => $conversation->id,
            'user_id'           => $sender->id,
            'type'              => $data['type'] ?? 'text',
            'body'              => $data['body'] ?? null,
            'reply_to_id'       => $data['reply_to_id'] ?? null,
            'forwarded_from_id' => $data['forwarded_from_id'] ?? null,
        ]);

        if (!empty($data['mentions'])) {
            foreach ($data['mentions'] as $userId) {
                $message->mentions()->create(['user_id' => $userId]);
            }
        }

        $conversation->update(['last_message_at' => now()]);
        if (!empty($data['media_ids'])) {
    \App\Domains\Media\Models\Media::whereIn('id', $data['media_ids'])
        ->where('user_id', $sender->id)
        ->update(['message_id' => $message->id]);
}

        $this->markMessageAsRead($message, $sender);

        $message->load(['sender', 'replyTo.sender', 'reactions', 'reads', 'mentions', 'media']);

        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }

    public function editMessage(Message $message, User $user, string $newBody): Message
    {
        if (!$message->isOwnedBy($user->id)) {
            throw ValidationException::withMessages([
                'message' => ['لا يمكنك تعديل رسائل الآخرين.'],
            ]);
        }

        $message->update([
            'body'      => $newBody,
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        $updated = $message->fresh(['sender', 'reactions', 'reads', 'mentions', 'media']);

        broadcast(new MessageUpdated($updated))->toOthers();

        return $updated;
    }

    public function deleteMessage(Message $message, User $user, Conversation $conversation): void
    {
        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->first();

        $isOwner = $message->isOwnedBy($user->id);
        $isAdmin = $participant?->isAdmin();

        if (!$isOwner && !$isAdmin) {
            throw ValidationException::withMessages([
                'message' => ['لا يمكنك حذف هذه الرسالة.'],
            ]);
        }

        $messageId      = $message->id;
        $conversationId = $message->conversation_id;

        $message->delete();

        broadcast(new MessageDeleted($messageId, $conversationId))->toOthers();
    }

    public function toggleReaction(Message $message, User $user, string $emoji): array
    {
        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            $existing->delete();
            $action = 'removed';
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id'    => $user->id,
                'emoji'      => $emoji,
            ]);
            $action = 'added';
        }

        return ['action' => $action, 'emoji' => $emoji];
    }

    public function pinMessage(Message $message, User $user, Conversation $conversation): Message
    {
        $participant = $conversation->participants()->where('user_id', $user->id)->first();

        if (!$participant?->isAdmin() && !$message->isOwnedBy($user->id)) {
            throw ValidationException::withMessages([
                'message' => ['لا يمكنك تثبيت هذه الرسالة.'],
            ]);
        }

        $message->update([
            'is_pinned' => true,
            'pinned_at' => now(),
            'pinned_by' => $user->id,
        ]);

        return $message->fresh();
    }

    public function markMessageAsRead(Message $message, User $user): void
    {
        MessageRead::firstOrCreate([
            'message_id' => $message->id,
            'user_id'    => $user->id,
        ], [
            'read_at' => now(),
        ]);
    }
}