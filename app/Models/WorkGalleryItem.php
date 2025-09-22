<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkGalleryItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'work_id',
        'type',
        'images',
        'order',
        'caption',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'images' => 'array',
    ];

    /**
     * Get the work that owns the gallery item.
     */
    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope to filter by gallery type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the expected number of images for this gallery type.
     */
    public function getExpectedImageCount(): int
    {
        return match($this->type) {
            'full-width' => 1,
            '2col-full', '2col-4:5', 'compare-full' => 2,
            default => 1,
        };
    }

    /**
     * Check if this gallery item has the correct number of images.
     */
    public function hasValidImageCount(): bool
    {
        return count($this->images) === $this->getExpectedImageCount();
    }
}
