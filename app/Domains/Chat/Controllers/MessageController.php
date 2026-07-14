<?php

namespace App\Domains\Chat\Controllers;

use App\Domains\Chat\Requests\EditMessageRequest;
use App\Domains\Chat\Requests\SendMessageRequest;
use App\Domains\Chat\Resources\MessageResource;
use App\Domains\Chat\Services\ConversationService;
use App\Domains\Chat\Services\MessageService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Domains\Chat\Models\Message;

class MessageController extends Controller
{
    public function __construct(
        private MessageService $messageService,
        private ConversationService $conversationService
    ) {}

    public function index(Request $request, int $conversationId): JsonResponse
    {
        $conversation = $this->conversationService->getConversation($conversationId, $request->user());
        $messages     = $this->messageService->getMessages($conversation, $request->user());

        return response()->json([
            'status' => true,
            'data'   => MessageResource::collection($messages),
            'meta'   => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'total'        => $messages->total(),
            ],
        ]);
    }

    public function store(SendMessageRequest $request, int $conversationId): JsonResponse
    {
        $conversation = $this->conversationService->getConversation($conversationId, $request->user());
        $message      = $this->messageService->sendMessage($conversation, $request->user(), $request->validated());

        return response()->json([
            'message' => 'تم إرسال الرسالة بنجاح.',
            'status'  => true,
            'data'    => new MessageResource($message),
        ], 201);
    }

    public function update(EditMessageRequest $request, int $conversationId, int $messageId): JsonResponse
    {
        $conversation = $this->conversationService->getConversation($conversationId, $request->user());
        $message      = $conversation->messages()->findOrFail($messageId);
        $updated      = $this->messageService->editMessage($message, $request->user(), $request->input('body'));

        return response()->json([
            'message' => 'تم تعديل الرسالة بنجاح.',
            'status'  => true,
            'data'    => new MessageResource($updated),
        ]);
    }

    public function destroy(Request $request, int $conversationId, int $messageId): JsonResponse
    {
        $conversation = $this->conversationService->getConversation($conversationId, $request->user());
        $message      = $conversation->messages()->findOrFail($messageId);
        $this->messageService->deleteMessage($message, $request->user(), $conversation);

        return response()->json([
            'message' => 'تم حذف الرسالة بنجاح.',
            'status'  => true,
        ]);
    }

    public function react(Request $request, int $conversationId, int $messageId): JsonResponse
    {
        $request->validate([
            'emoji' => ['required', 'string', 'max:10'],
        ]);

        $conversation = $this->conversationService->getConversation($conversationId, $request->user());
        $message      = $conversation->messages()->findOrFail($messageId);
        $result       = $this->messageService->toggleReaction($message, $request->user(), $request->input('emoji'));

        return response()->json([
            'message' => $result['action'] === 'added' ? 'تم إضافة التفاعل.' : 'تم إزالة التفاعل.',
            'status'  => true,
            'data'    => $result,
        ]);
    }

    public function pin(Request $request, int $conversationId, int $messageId): JsonResponse
    {
        $conversation = $this->conversationService->getConversation($conversationId, $request->user());
        $message      = $conversation->messages()->findOrFail($messageId);
        $pinned       = $this->messageService->pinMessage($message, $request->user(), $conversation);

        return response()->json([
            'message' => 'تم تثبيت الرسالة بنجاح.',
            'status'  => true,
            'data'    => new MessageResource($pinned),
        ]);
    }

    public function pinned(Request $request, int $conversationId): JsonResponse
    {
    $conversation = $this->conversationService->getConversation($conversationId, $request->user());

    $pinned = Message::where('conversation_id', $conversation->id)
        ->where('is_pinned', true)
        ->with(['sender', 'reactions', 'reads'])
        ->latest('pinned_at')
        ->get();

    return response()->json([
        'status' => true,
        'data'   => MessageResource::collection($pinned),
    ]);
    }

    public function forward(Request $request, int $conversationId, int $messageId): JsonResponse
{
    $request->validate([
        'target_conversation_id' => ['required', 'integer', 'exists:conversations,id'],
    ]);

    $sourceConversation = $this->conversationService->getConversation($conversationId, $request->user());
    $targetConversation = $this->conversationService->getConversation($request->input('target_conversation_id'), $request->user());

    $originalMessage = $sourceConversation->messages()->findOrFail($messageId);

    $forwarded = $this->messageService->sendMessage($targetConversation, $request->user(), [
        'body'              => $originalMessage->body,
        'type'              => $originalMessage->type,
        'forwarded_from_id' => $originalMessage->id,
    ]);

    return response()->json([
        'message' => 'تم إعادة توجيه الرسالة بنجاح.',
        'status'  => true,
        'data'    => new MessageResource($forwarded),
    ], 201);
}

}