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
        'url'
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
}
