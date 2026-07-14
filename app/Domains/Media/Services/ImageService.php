<?php

namespace App\Domains\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class ImageService
{
    public function processImage(UploadedFile $file, string $directory): array
    {
        $manager = ImageManager::gd();
        $image   = $manager->read($file->getPathname());
        $width   = $image->width();
        $height  = $image->height();

        if ($width > 1920 || $height > 1080) {
            $image->scaleDown(1920, 1080);
            $width  = $image->width();
            $height = $image->height();
        }

        $filename = Str::random(20) . '.webp';
        $path     = $directory . '/' . $filename;
        $fullPath = storage_path('app/public/' . $path);

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $image->toWebp(85)->save($fullPath);

        $thumbnailPath = $this->generateThumbnail($manager, $file, $directory, $filename);

        return [
            'path'      => $path,
            'thumbnail' => $thumbnailPath,
            'width'     => $width,
            'height'    => $height,
            'size'      => filesize($fullPath),
        ];
    }

    private function generateThumbnail($manager, UploadedFile $file, string $directory, string $filename): string
    {
        $thumbFilename = 'thumb_' . $filename;
        $thumbPath     = $directory . '/thumbnails/' . $thumbFilename;
        $fullThumbPath = storage_path('app/public/' . $thumbPath);

        if (!file_exists(dirname($fullThumbPath))) {
            mkdir(dirname($fullThumbPath), 0755, true);
        }

        $thumb = $manager->read($file->getPathname());
        $thumb->cover(300, 300);
        $thumb->toWebp(70)->save($fullThumbPath);

        return $thumbPath;
    }

    public function deleteImage(string $path): void
    {
        Storage::disk('public')->delete($path);

        $directory = dirname($path);
        $filename  = 'thumb_' . pathinfo($path, PATHINFO_BASENAME);
        Storage::disk('public')->delete($directory . '/thumbnails/' . $filename);
    }
}