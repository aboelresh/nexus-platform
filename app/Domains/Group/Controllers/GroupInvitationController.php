<?php

namespace App\Domains\Group\Controllers;

use App\Domains\Group\Models\Group;
use App\Domains\Group\Requests\InviteMemberRequest;
use App\Domains\Group\Resources\GroupResource;
use App\Domains\Group\Services\GroupInvitationService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupInvitationController extends Controller
{
    public function __construct(private GroupInvitationService $invitationService) {}

    public function invite(InviteMemberRequest $request, int $groupId): JsonResponse
    {
        $group      = Group::findOrFail($groupId);
        $invitation = $this->invitationService->invite($group, $request->user(), $request->input('user_id'));
        return response()->json([
            'message' => 'تم إرسال الدعوة بنجاح.',
            'status'  => true,
            'data'    => ['token' => $invitation->token, 'expires_at' => $invitation->expires_at->toISOString()],
        ], 201);
    }

    public function accept(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);
        $group = $this->invitationService->acceptInvitation($request->user(), $request->input('token'));
        return response()->json(['message' => 'تم قبول الدعوة وانضممت للمجموعة.', 'status' => true, 'data' => new GroupResource($group)]);
    }

    public function decline(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);
        $this->invitationService->declineInvitation($request->user(), $request->input('token'));
        return response()->json(['message' => 'تم رفض الدعوة.', 'status' => true]);
    }

    public function myInvitations(Request $request): JsonResponse
    {
        $invitations = $this->invitationService->getUserInvitations($request->user());
        return response()->json(['status' => true, 'data' => $invitations]);
    }
}