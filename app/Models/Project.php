<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'location',
        'year',
        'description',
        'display_order',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected $appends = ['images'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->uuid)) {
                $project->uuid = (string) Str::uuid();
            }

            // Auto-assign display_order to the end
            if (empty($project->display_order)) {
                $maxOrder = static::max('display_order') ?? 0;
                $project->display_order = $maxOrder + 1;
            }

            // Set published_at when status is published
            if ($project->status === 'published' && empty($project->published_at)) {
                $project->published_at = now();
            }
        });

        static::updating(function ($project) {
            // Update published_at when publishing
            if ($project->isDirty('status')) {
                if ($project->status === 'published' && empty($project->published_at)) {
                    $project->published_at = now();
                } elseif ($project->status === 'draft') {
                    $project->published_at = null;
                }
            }
        });
    }

    /**
     * Get the project images.
     */
    public function projectImages()
    {
        return $this->hasMany(ProjectImage::class)->orderBy('display_order');
    }

    /**
     * Get images attribute for API response.
     */
    public function getImagesAttribute()
    {
        return $this->projectImages()
            ->with('media')
            ->get()
            ->map(function ($projectImage) {
                return [
                    'id' => $projectImage->id,
                    'media_id' => $projectImage->media_id,
                    'display_order' => $projectImage->display_order,
                    'url' => $projectImage->media ? $projectImage->media->url : null,
                    'filename' => $projectImage->media ? $projectImage->media->filename : null,
                ];
            });
    }

    /**
     * Scope to get only published projects.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at');
    }
}
