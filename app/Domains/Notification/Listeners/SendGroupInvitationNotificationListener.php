<?php

namespace App\Domains\Notification\Listeners;

use App\Domains\Notification\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendGroupInvitationNotificationListener implements ShouldQueue
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(object $event): void
    {
        $this->notificationService->notifyGroupInvitation(
            $event->invitation,
            $event->group,
            $event->invitedBy
        );
    }
}