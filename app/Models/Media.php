<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'filename',
        'original_name',
        'mime_type',
        'size',
        'path',
        'extension',
        'alt_text',
        'description',
        'is_active',
        'poster_path',
        'poster_filename'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'size' => 'integer'
    ];

    protected $appends = [
        'is_image',
        'is_video',
        'url',
        'poster_url'
    ];

    /**
     * Get the full URL for the media file
     */
    public function getUrlAttribute()
    {
        // Return direct public URL for better performance and public access
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Get the API serve URL for the media file (fallback)
     */
    public function getServeUrlAttribute()
    {
        return route('media.serve', $this->filename);
    }

    /**
     * Check if file is an image
     */
    public function getIsImageAttribute()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if file is a video
     */
    public function getIsVideoAttribute()
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Get the poster URL for video files
     */
    public function getPosterUrlAttribute()
    {
        if ($this->is_video && $this->poster_path && $this->poster_path !== '0') {
            return Storage::disk('public')->url($this->poster_path);
        }
        return null;
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Scope for active media only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for images only
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope for videos only
     */
    public function scopeVideos($query)
    {
        return $query->where('mime_type', 'like', 'video/%');
    }
}
