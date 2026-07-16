<?php

namespace App\Domains\Group\Controllers;

use App\Domains\Group\Requests\CreateGroupRequest;
use App\Domains\Group\Requests\UpdateGroupRequest;
use App\Domains\Group\Resources\GroupResource;
use App\Domains\Group\Services\GroupService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(private GroupService $groupService) {}

    public function index(Request $request): JsonResponse
    {
        $groups = $this->groupService->getUserGroups($request->user());
        return response()->json([
            'status' => true,
            'data'   => GroupResource::collection($groups),
            'meta'   => ['current_page' => $groups->currentPage(), 'last_page' => $groups->lastPage(), 'total' => $groups->total()],
        ]);
    }

    public function store(CreateGroupRequest $request): JsonResponse
    {
        $group = $this->groupService->create($request->user(), $request->validated());
        return response()->json(['message' => 'تم إنشاء المجموعة بنجاح.', 'status' => true, 'data' => new GroupResource($group)], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $group = $this->groupService->getGroup($id, $request->user());
        return response()->json(['status' => true, 'data' => new GroupResource($group)]);
    }

    public function update(UpdateGroupRequest $request, int $id): JsonResponse
    {
        $group = $this->groupService->getGroup($id, $request->user());
        $updated = $this->groupService->update($group, $request->user(), $request->validated());
        return response()->json(['message' => 'تم تحديث المجموعة بنجاح.', 'status' => true, 'data' => new GroupResource($updated)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $group = $this->groupService->getGroup($id, $request->user());
        $this->groupService->delete($group, $request->user());
        return response()->json(['message' => 'تم حذف المجموعة بنجاح.', 'status' => true]);
    }

    public function join(Request $request, int $id): JsonResponse
    {
        $group = \App\Domains\Group\Models\Group::findOrFail($id);
        $this->groupService->join($group, $request->user());
        return response()->json(['message' => 'تم الانضمام للمجموعة بنجاح.', 'status' => true]);
    }

    public function leave(Request $request, int $id): JsonResponse
    {
        $group = $this->groupService->getGroup($id, $request->user());
        $this->groupService->leave($group, $request->user());
        return response()->json(['message' => 'تم مغادرة المجموعة بنجاح.', 'status' => true]);
    }

    public function transferOwnership(Request $request, int $id): JsonResponse
    {
        $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $group = $this->groupService->getGroup($id, $request->user());
        $this->groupService->transferOwnership($group, $request->user(), $request->input('user_id'));
        return response()->json(['message' => 'تم نقل الملكية بنجاح.', 'status' => true]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2'], 'per_page' => ['sometimes', 'integer', 'min:5', 'max:50']]);
        $groups = $this->groupService->searchPublicGroups($request->input('q'), $request->input('per_page', 20));
        return response()->json([
            'status' => true,
            'data'   => GroupResource::collection($groups),
            'meta'   => ['current_page' => $groups->currentPage(), 'last_page' => $groups->lastPage(), 'total' => $groups->total()],
        ]);
    }
}