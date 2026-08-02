<?php

namespace App\Domains\Call\Controllers;

use App\Domains\Call\Resources\CallResource;
use App\Domains\Call\Services\CallService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function __construct(private CallService $callService) {}

    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => ['required', 'integer', 'exists:conversations,id'],
            'type'            => ['required', 'string', 'in:voice,video'],
        ]);

        $call = $this->callService->initiateCall(
            $request->user(),
            $request->input('conversation_id'),
            $request->input('type')
        );

        return response()->json([
            'message' => 'جاري الاتصال...',
            'status'  => true,
            'data'    => new CallResource($call),
        ], 201);
    }

    public function answer(Request $request, int $callId): JsonResponse
    {
        $call = $this->callService->answerCall($request->user(), $callId);

        return response()->json([
            'message' => 'تم قبول المكالمة.',
            'status'  => true,
            'data'    => new CallResource($call),
        ]);
    }

    public function reject(Request $request, int $callId): JsonResponse
    {
        $call = $this->callService->rejectCall($request->user(), $callId);

        return response()->json([
            'message' => 'تم رفض المكالمة.',
            'status'  => true,
            'data'    => new CallResource($call),
        ]);
    }

    public function end(Request $request, int $callId): JsonResponse
    {
        $call = $this->callService->endCall($request->user(), $callId);

        return response()->json([
            'message' => 'تم إنهاء المكالمة.',
            'status'  => true,
            'data'    => new CallResource($call),
        ]);
    }

    public function signal(Request $request, int $callId): JsonResponse
    {
        $request->validate([
            'to_user_id'  => ['required', 'integer', 'exists:users,id'],
            'signal_type' => ['required', 'string', 'in:offer,answer,ice-candidate,renegotiate'],
            'payload'     => ['required', 'array'],
        ]);

        $this->callService->handleSignal(
            $request->user(),
            $callId,
            $request->input('to_user_id'),
            $request->input('signal_type'),
            $request->input('payload')
        );

        return response()->json(['status' => true]);
    }

    public function toggleMute(Request $request, int $callId): JsonResponse
    {
        $participant = $this->callService->toggleMute($request->user(), $callId);

        return response()->json([
            'status' => true,
            'data'   => ['is_muted' => $participant->is_muted],
        ]);
    }

    public function toggleCamera(Request $request, int $callId): JsonResponse
    {
        $participant = $this->callService->toggleCamera($request->user(), $callId);

        return response()->json([
            'status' => true,
            'data'   => ['camera_on' => $participant->camera_on],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $calls = $this->callService->getCallHistory(
            $request->user(),
            $request->integer('per_page', 20)
        );

        return response()->json([
            'status' => true,
            'data'   => CallResource::collection($calls),
            'meta'   => [
                'current_page' => $calls->currentPage(),
                'last_page'    => $calls->lastPage(),
                'total'        => $calls->total(),
            ],
        ]);
    }

    public function active(Request $request, int $conversationId): JsonResponse
    {
        $call = $this->callService->getActiveCall($conversationId);

        return response()->json([
            'status' => true,
            'data'   => $call ? new CallResource($call) : null,
        ]);
    }
}