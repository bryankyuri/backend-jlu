<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VideoBanner extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'work_id',
        'video_url',
        'video_thumbnail',
        'is_custom_video',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_custom_video' => 'boolean',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    protected $hidden = [];

    /**
     * Relationship with Work model
     */
    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    /**
     * Scope to get only active banners
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Get the next available position
     */
    public static function getNextPosition()
    {
        return static::active()->max('position') + 1;
    }

    /**
     * Reorder banners based on new positions
     */
    public static function reorderBanners(array $bannerPositions)
    {
        \DB::transaction(function () use ($bannerPositions) {
            // First, temporarily set all positions to negative values to avoid conflicts
            foreach ($bannerPositions as $index => $item) {
                static::where('id', $item['id'])
                    ->update(['position' => -($index + 1)]);
            }
            
            // Then set the actual positions
            foreach ($bannerPositions as $item) {
                static::where('id', $item['id'])
                    ->update(['position' => $item['position']]);
            }
        });
    }

    /**
     * Ensure maximum 4 active banners
     */
    public static function enforceMaxLimit()
    {
        $activeBanners = static::active()->ordered()->get();
        
        if ($activeBanners->count() > 4) {
            // Deactivate banners beyond position 4
            $bannersToDeactivate = $activeBanners->slice(4);
            foreach ($bannersToDeactivate as $banner) {
                $banner->update(['is_active' => false]);
            }
        }
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($banner) {
            // Set position if not provided
            if (!$banner->position) {
                $banner->position = static::getNextPosition();
            }
            
            // Ensure max 4 active banners
            $activeCount = static::active()->count();
            if ($activeCount >= 4) {
                // Find the banner with highest position and deactivate it
                $lastBanner = static::active()->orderBy('position', 'desc')->first();
                if ($lastBanner) {
                    $lastBanner->update(['is_active' => false]);
                }
            }
        });

        static::updated(function ($banner) {
            // Ensure max limit after updates
            static::enforceMaxLimit();
        });
    }
}