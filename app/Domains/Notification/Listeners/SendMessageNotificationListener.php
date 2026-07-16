<?php

namespace App\Domains\Notification\Listeners;

use App\Domains\Chat\Events\MessageSent;
use App\Domains\Notification\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMessageNotificationListener implements ShouldQueue
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $sender  = $message->sender;

        if (!$sender) return;

        $this->notificationService->notifyNewMessage($message, $sender);

        $mentionedIds = $message->mentions()->pluck('user_id')->toArray();
        if (!empty($mentionedIds)) {
            $this->notificationService->notifyMentions($message, $sender, $mentionedIds);
        }
    }
}