<?php

namespace App\Domains\Media\Services;

use App\Domains\Media\Models\Media;
use App\Domains\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaService
{
    private array $allowedTypes = [
        'image'    => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'video'    => ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm'],
        'voice' => [
    'audio/mpeg',
    'audio/wav',
    'audio/ogg',
    'audio/webm',
    'audio/mp4',
    'audio/aac',
    'video/mp4',
    'application/octet-stream',
],
'audio' => [
    'audio/mpeg',
    'audio/wav',
    'audio/ogg',
    'audio/mp4',
    'audio/aac',
    'video/mp4',
    'application/octet-stream',
],
        'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'text/plain'],

    ];

    private array $maxSizes = [
        'image'    => 10 * 1024 * 1024,
        'video'    => 100 * 1024 * 1024,
        'audio'    => 20 * 1024 * 1024,
        'document' => 50 * 1024 * 1024,
        'voice'    => 10 * 1024 * 1024,
    ];

    public function __construct(
        private ImageService $imageService
    ) {}

    public function upload(UploadedFile $file, string $type, User $user, ?int $messageId = null): Media
    {
        $this->validateFile($file, $type);

        $result = match($type) {
            'image'  => $this->handleImage($file, $user),
            'voice'  => $this->handleVoice($file, $user),
            default  => $this->handleFile($file, $type, $user),
        };

        return Media::create([
            'user_id'       => $user->id,
            'message_id'    => $messageId,
            'disk'          => 'public',
            'path'          => $result['path'],
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'type'          => $type,
            'size'          => $result['size'],
            'width'         => $result['width'] ?? null,
            'height'        => $result['height'] ?? null,
            'duration'      => $result['duration'] ?? null,
            'thumbnail'     => $result['thumbnail'] ?? null,
            'metadata'      => $result['metadata'] ?? null,
        ]);
    }

    public function delete(Media $media, User $user): void
    {
        if ($media->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'media' => ['لا يمكنك حذف هذا الملف.'],
            ]);
        }

        if ($media->type === 'image') {
            $this->imageService->deleteImage($media->path);
        } else {
            Storage::disk('public')->delete($media->path);
        }

        $media->delete();
    }

    public function getConversationMedia(int $conversationId, ?string $type = null, int $perPage = 30)
    {
        $query = Media::whereHas('message', fn($q) => $q->where('conversation_id', $conversationId))
            ->with('user')
            ->latest();

        if ($type) {
            $query->where('type', $type);
        }

        return $query->paginate($perPage);
    }

    private function validateFile(UploadedFile $file, string $type): void
    {
        $allowedMimes = $this->allowedTypes[$type] ?? [];
        $maxSize      = $this->maxSizes[$type] ?? 10 * 1024 * 1024;

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw ValidationException::withMessages([
                'file' => ['نوع الملف غير مسموح به لهذه الفئة.'],
            ]);
        }

        if ($file->getSize() > $maxSize) {
            $maxMB = $maxSize / (1024 * 1024);
            throw ValidationException::withMessages([
                'file' => ["حجم الملف يتجاوز الحد المسموح به ({$maxMB} ميجابايت)."],
            ]);
        }
    }

    private function handleImage(UploadedFile $file, User $user): array
    {
        $directory = 'media/images/' . $user->id;
        return $this->imageService->processImage($file, $directory);
    }

    private function handleVoice(UploadedFile $file, User $user): array
    {
        $directory = 'media/voice/' . $user->id;
        $filename  = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $path      = $file->storeAs($directory, $filename, 'public');

        return [
            'path' => $path,
            'size' => $file->getSize(),
        ];
    }

    private function handleFile(UploadedFile $file, string $type, User $user): array
    {
        $directory = 'media/' . $type . '/' . $user->id;
        $filename  = Str::random(20) . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $path      = $file->storeAs($directory, $filename, 'public');

        return [
            'path' => $path,
            'size' => $file->getSize(),
        ];
    }
}