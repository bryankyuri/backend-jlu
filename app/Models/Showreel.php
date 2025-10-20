<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Showreel extends Model
{
    protected $fillable = [
        'title',
        'description',
        'video_url',
        'video_source_type',
        'video_youtube_url',
        'video_vimeo_url',
        'video_cloudflare_url',
        'poster_media_id',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    protected $appends = [
        'poster_url',
        'video_embed_url',
    ];

    /**
     * Get the poster media
     */
    public function posterMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'poster_media_id');
    }

    /**
     * Get poster URL attribute
     */
    public function getPosterUrlAttribute(): ?string
    {
        if ($this->posterMedia) {
            return $this->posterMedia->url;
        }
        return null;
    }

    /**
     * Get video embed URL based on source type
     */
    public function getVideoEmbedUrlAttribute(): string
    {
        if ($this->video_source_type === 'youtube') {
            // Extract YouTube video ID and return embed URL
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $this->video_url, $matches);
            if (isset($matches[1])) {
                return "https://www.youtube.com/embed/{$matches[1]}";
            }
        } elseif ($this->video_source_type === 'vimeo') {
            // Extract Vimeo video ID and return embed URL
            preg_match('/vimeo\.com\/(?:video\/)?(\d+)/i', $this->video_url, $matches);
            if (isset($matches[1])) {
                return "https://player.vimeo.com/video/{$matches[1]}";
            }
        }
        
        return $this->video_url;
    }

    /**
     * Scope for ordered showreels
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc');
    }
}
