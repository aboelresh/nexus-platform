<?php

namespace App\Domains\Group\Controllers;

use App\Domains\Group\Models\Group;
use App\Domains\Group\Resources\GroupMemberResource;
use App\Domains\Group\Services\GroupMemberService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupMemberController extends Controller
{
    public function __construct(private GroupMemberService $memberService) {}

    public function index(Request $request, int $groupId): JsonResponse
    {
        $group   = Group::findOrFail($groupId);
        $members = $this->memberService->getMembers($group, $request->user());
        return response()->json([
            'status' => true,
            'data'   => GroupMemberResource::collection($members),
            'meta'   => ['current_page' => $members->currentPage(), 'last_page' => $members->lastPage(), 'total' => $members->total()],
        ]);
    }

    public function changeRole(Request $request, int $groupId, int $userId): JsonResponse
    {
        $request->validate(['role' => ['required', 'in:admin,moderator,member']]);
        $group  = Group::findOrFail($groupId);
        $member = $this->memberService->changeRole($group, $request->user(), $userId, $request->input('role'));
        return response()->json(['message' => 'تم تغيير الدور بنجاح.', 'status' => true, 'data' => new GroupMemberResource($member)]);
    }

    public function kick(Request $request, int $groupId, int $userId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $this->memberService->kickMember($group, $request->user(), $userId);
        return response()->json(['message' => 'تم طرد العضو بنجاح.', 'status' => true]);
    }

    public function ban(Request $request, int $groupId, int $userId): JsonResponse
    {
        $request->validate(['reason' => ['sometimes', 'nullable', 'string', 'max:200']]);
        $group = Group::findOrFail($groupId);
        $this->memberService->banMember($group, $request->user(), $userId, $request->input('reason'));
        return response()->json(['message' => 'تم حظر العضو بنجاح.', 'status' => true]);
    }

    public function unban(Request $request, int $groupId, int $userId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $this->memberService->unbanMember($group, $request->user(), $userId);
        return response()->json(['message' => 'تم إلغاء حظر العضو.', 'status' => true]);
    }

    public function mute(Request $request, int $groupId, int $userId): JsonResponse
    {
        $request->validate(['minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10080']]);
        $group = Group::findOrFail($groupId);
        $this->memberService->muteMember($group, $request->user(), $userId, $request->input('minutes'));
        return response()->json(['message' => 'تم كتم العضو.', 'status' => true]);
    }

    public function unmute(Request $request, int $groupId, int $userId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $this->memberService->unmuteMember($group, $request->user(), $userId);
        return response()->json(['message' => 'تم إلغاء الكتم.', 'status' => true]);
    }

    public function joinRequests(Request $request, int $groupId): JsonResponse
    {
        $group    = Group::findOrFail($groupId);
        $requests = $this->memberService->getJoinRequests($group, $request->user());
        return response()->json(['status' => true, 'data' => $requests]);
    }

    public function reviewRequest(Request $request, int $groupId, int $requestId): JsonResponse
    {
        $request->validate(['approve' => ['required', 'boolean']]);
        $group  = Group::findOrFail($groupId);
        $result = $this->memberService->reviewJoinRequest($group, $request->user(), $requestId, $request->boolean('approve'));
        $msg    = $request->boolean('approve') ? 'تم قبول الطلب.' : 'تم رفض الطلب.';
        return response()->json(['message' => $msg, 'status' => true, 'data' => $result]);
    }
}