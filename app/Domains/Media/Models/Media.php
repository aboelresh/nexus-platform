<?php

namespace App\Domains\Media\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'user_id',
        'message_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'type',
        'size',
        'duration',
        'width',
        'height',
        'thumbnail',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'size'     => 'integer',
            'duration' => 'integer',
            'width'    => 'integer',
            'height'   => 'integer',
            'metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function message()
    {
        return $this->belongsTo(\App\Domains\Chat\Models\Message::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) return null;
        return asset('storage/' . $this->thumbnail);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}