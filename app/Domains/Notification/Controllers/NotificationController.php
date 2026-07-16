<?php

namespace App\Domains\Notification\Controllers;

use App\Domains\Notification\Services\NotificationService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'unread_only' => ['sometimes', 'boolean'],
            'per_page'    => ['sometimes', 'integer', 'min:5', 'max:50'],
        ]);

        $notifications = $this->notificationService->getUserNotifications(
            $request->user(),
            $request->boolean('unread_only', false),
            $request->integer('per_page', 20)
        );

        return response()->json([
            'status' => true,
            'data'   => $notifications->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'data'       => $n->data,
                'read_at'    => $n->read_at?->toISOString(),
                'created_at' => $n->created_at->toISOString(),
            ]),
            'meta'   => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'total'        => $notifications->total(),
                'unread_count' => $this->notificationService->getUnreadCount($request->user()),
            ],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => $this->notificationService->getNotificationStats($request->user()),
        ]);
    }

    public function markAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_id' => ['sometimes', 'nullable', 'string'],
        ]);

        $this->notificationService->markAsRead(
            $request->user(),
            $request->input('notification_id')
        );

        return response()->json([
            'message' => 'تم تعليم الإشعارات كمقروءة.',
            'status'  => true,
        ]);
    }

    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        $this->notificationService->deleteNotification($request->user(), $notificationId);

        return response()->json([
            'message' => 'تم حذف الإشعار.',
            'status'  => true,
        ]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $this->notificationService->deleteAllNotifications($request->user());

        return response()->json([
            'message' => 'تم حذف جميع الإشعارات.',
            'status'  => true,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => ['unread_count' => $this->notificationService->getUnreadCount($request->user())],
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
{
    $validated = $request->validate([
        'email'    => ['sometimes', 'boolean'],
        'push'     => ['sometimes', 'boolean'],
        'in_app'   => ['sometimes', 'boolean'],
        'mentions' => ['sometimes', 'boolean'],
        'messages' => ['sometimes', 'boolean'],
        'groups'   => ['sometimes', 'boolean'],
        'calls'    => ['sometimes', 'boolean'],
    ]);

    $user    = $request->user();
    $current = $user->notification_preferences ?? [];
    $updated = array_merge($current, $validated);

    $user->update(['notification_preferences' => $updated]);

    return response()->json([
        'message' => 'تم تحديث تفضيلات الإشعارات.',
        'status'  => true,
        'data'    => ['notification_preferences' => $updated],
    ]);
}
}