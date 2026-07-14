<?php

use App\Domains\Chat\Models\Conversation;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (!$conversation) return false;
    return $conversation->hasParticipant($user->id);
});

Broadcast::channel('presence', function (User $user) {
    return [
        'id'       => $user->id,
        'name'     => $user->name,
        'username' => $user->username,
        'avatar'   => $user->avatar_url,
    ];
});

Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});