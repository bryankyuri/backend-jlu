<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Work extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'client',
        'category',
        'year',
        'description',
        'hero_banner_image',
        'video_project_src',
        'video_project_poster',
        'tags',
        'status',
        'slug',
        'meta_description',
        'display_order',
        'created_by',
        'updated_by',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'created_by',
        'updated_by',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug when creating
        static::creating(function ($work) {
            if (empty($work->slug)) {
                $work->slug = Str::slug($work->title);
            }
            
            // Set published_at timestamp if status is published
            if ($work->status === 'published' && !$work->published_at) {
                $work->published_at = now();
            }
        });

        // Update slug and published_at when updating
        static::updating(function ($work) {
            if ($work->isDirty('title') && empty($work->slug)) {
                $work->slug = Str::slug($work->title);
            }
            
            // Set published_at when status changes to published
            if ($work->isDirty('status') && $work->status === 'published' && !$work->published_at) {
                $work->published_at = now();
            }
            
            // Clear published_at when status changes from published
            if ($work->isDirty('status') && $work->status !== 'published') {
                $work->published_at = null;
            }
        });
    }

    /**
     * Get the credits for the work.
     */
    public function credits(): HasMany
    {
        return $this->hasMany(WorkCredit::class)->orderBy('order');
    }

    /**
     * Get the gallery items for the work.
     */
    public function galleryItems(): HasMany
    {
        return $this->hasMany(WorkGalleryItem::class)->orderBy('order');
    }

    /**
     * Get the user who created this work.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this work.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include published works.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include draft works.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Check if the work is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if the work is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Publish the work.
     */
    public function publish(): bool
    {
        $this->status = 'published';
        $this->published_at = now();
        return $this->save();
    }

    /**
     * Unpublish the work (make it draft).
     */
    public function unpublish(): bool
    {
        $this->status = 'draft';
        $this->published_at = null;
        return $this->save();
    }
}
