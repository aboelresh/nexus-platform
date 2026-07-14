<?php

namespace App\Domains\Chat\Controllers;

use App\Domains\Chat\Events\UserTyping;
use App\Domains\Chat\Requests\CreateConversationRequest;
use App\Domains\Chat\Resources\ConversationResource;
use App\Domains\Chat\Services\ConversationService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(
        private ConversationService $conversationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $conversations = $this->conversationService->getUserConversations($request->user());

        return response()->json([
            'status' => true,
            'data'   => ConversationResource::collection($conversations),
            'meta'   => [
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
                'total'        => $conversations->total(),
            ],
        ]);
    }

    public function store(CreateConversationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $conversation = match($data['type']) {
            'direct' => $this->conversationService->createDirect($request->user(), $data['user_id']),
            'group'  => $this->conversationService->createGroup($request->user(), $data),
        };

        return response()->json([
            'message' => 'تم إنشاء المحادثة بنجاح.',
            'status'  => true,
            'data'    => new ConversationResource($conversation),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversationService->getConversation($id, $request->user());

        return response()->json([
            'status' => true,
            'data'   => new ConversationResource($conversation),
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversationService->getConversation($id, $request->user());
        $this->conversationService->markAsRead($conversation, $request->user());

        return response()->json([
            'message' => 'تم تعليم المحادثة كمقروءة.',
            'status'  => true,
        ]);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversationService->getConversation($id, $request->user());
        $this->conversationService->archiveConversation($conversation, $request->user());

        return response()->json([
            'message' => 'تم أرشفة المحادثة بنجاح.',
            'status'  => true,
        ]);
    }

    public function typing(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'is_typing' => ['required', 'boolean'],
        ]);

        $conversation = $this->conversationService->getConversation($id, $request->user());

        broadcast(new UserTyping(
            $request->user(),
            $conversation->id,
            $request->boolean('is_typing')
        ))->toOthers();

        return response()->json(['status' => true]);
    }
}