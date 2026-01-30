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
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'size' => 'integer'
    ];

    protected $appends = [
        'is_image',
        'is_video',
        'url'
    ];

    /**
     * Get the full URL for the media file
     */
    public function getUrlAttribute()
    {
        // Get the base URL based on environment
        $baseUrl = $this->getBaseUrl();
        
        // Return direct public URL for better performance and public access
        return $baseUrl . '/storage/' . $this->path;
    }

    /**
     * Get the base URL based on environment
     */
    private function getBaseUrl()
    {
        $env = config('app.env');
        
        switch ($env) {
            case 'production':
                return 'https://api.parallelstudio.asia';
            case 'staging':
                return 'https://staging-api.parallelstudio.asia';
            default:
                return config('app.url');
        }
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
        // Not used for images-only implementation
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
