<?php

namespace App\Domains\Media\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'url'            => $this->url,
            'thumbnail_url'  => $this->thumbnail_url,
            'original_name'  => $this->original_name,
            'mime_type'      => $this->mime_type,
            'size'           => $this->size,
            'formatted_size' => $this->formatted_size,
            'width'          => $this->width,
            'height'         => $this->height,
            'duration'       => $this->duration,
            'metadata'       => $this->metadata,
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}