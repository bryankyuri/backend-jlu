<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Work extends Model
{
    use HasFactory, HasUuids;

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the primary key is auto-incrementing.
     */
    public $incrementing = false;

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
        'hero_banner_position_x',
        'hero_banner_position_y',
        'video_project_src',
        'video_project_poster',
        'video_vimeo_url',
        'video_youtube_url',
        'video_cloudflare_url',
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
                $work->slug = static::generateUniqueSlug($work->title);
            }
            
            // Auto-assign display_order (max + 1) if not set
            if (is_null($work->display_order)) {
                $maxOrder = static::max('display_order') ?? 0;
                $work->display_order = $maxOrder + 1;
            }
            
            // Set published_at timestamp if status is published
            if ($work->status === 'published' && !$work->published_at) {
                $work->published_at = now();
            }
        });

        // Update slug and published_at when updating
        static::updating(function ($work) {
            if ($work->isDirty('title')) {
                $work->slug = static::generateUniqueSlug($work->title, $work->id);
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
     * Generate a unique slug for the work.
     */
    private static function generateUniqueSlug($title, $excludeId = null)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-(' . $counter . ')';
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists.
     */
    private static function slugExists($slug, $excludeId = null)
    {
        $query = static::where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
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
