<?php

namespace App\Domains\Media\Controllers;

use App\Domains\Media\Requests\UploadMediaRequest;
use App\Domains\Media\Resources\MediaResource;
use App\Domains\Media\Services\MediaService;
use App\Domains\Media\Models\Media;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(
        private MediaService $mediaService
    ) {}

    public function upload(UploadMediaRequest $request): JsonResponse
    {
        $media = $this->mediaService->upload(
            $request->file('file'),
            $request->input('type'),
            $request->user(),
            $request->input('message_id')
        );

        return response()->json([
            'message' => 'تم رفع الملف بنجاح.',
            'status'  => true,
            'data'    => new MediaResource($media),
        ], 201);
    }

    public function destroy(Request $request, int $mediaId): JsonResponse
    {
        $media = Media::findOrFail($mediaId);
        $this->mediaService->delete($media, $request->user());

        return response()->json([
            'message' => 'تم حذف الملف بنجاح.',
            'status'  => true,
        ]);
    }

    public function conversationMedia(Request $request, int $conversationId): JsonResponse
    {
        $request->validate([
            'type'     => ['sometimes', 'string', 'in:image,video,audio,document,voice'],
            'per_page' => ['sometimes', 'integer', 'min:5', 'max:50'],
        ]);

        $media = $this->mediaService->getConversationMedia(
            $conversationId,
            $request->input('type'),
            $request->input('per_page', 30)
        );

        return response()->json([
            'status' => true,
            'data'   => MediaResource::collection($media),
            'meta'   => [
                'current_page' => $media->currentPage(),
                'last_page'    => $media->lastPage(),
                'total'        => $media->total(),
            ],
        ]);
    }

    public function show(Request $request, int $mediaId): JsonResponse
    {
        $media = Media::with('user')->findOrFail($mediaId);

        return response()->json([
            'status' => true,
            'data'   => new MediaResource($media),
        ]);
    }
}