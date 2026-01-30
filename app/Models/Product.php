<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'category',
        'name',
        'capacity',
        'detail_specs',
        'tags',
        'display_order',
        'status',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
    ];

    protected $appends = ['images'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }

            // Auto-assign display_order within category
            if (empty($product->display_order)) {
                $maxOrder = static::where('category', $product->category)
                    ->max('display_order') ?? 0;
                $product->display_order = $maxOrder + 1;
            }

            // Set published_at when status is published
            if ($product->status === 'published' && empty($product->published_at)) {
                $product->published_at = now();
            }
        });

        static::updating(function ($product) {
            // Update published_at when publishing
            if ($product->isDirty('status')) {
                if ($product->status === 'published' && empty($product->published_at)) {
                    $product->published_at = now();
                } elseif ($product->status === 'draft') {
                    $product->published_at = null;
                }
            }
        });
    }



    /**
     * Get the product images.
     */
    public function productImages()
    {
        return $this->hasMany(ProductImage::class)->orderBy('display_order');
    }

    /**
     * Get images attribute for API response.
     */
    public function getImagesAttribute()
    {
        return $this->productImages()
            ->with('media')
            ->get()
            ->map(function ($productImage) {
                return [
                    'id' => $productImage->media_id, // Use media_id for consistency
                    'product_image_id' => $productImage->id, // Keep junction table ID if needed
                    'media_id' => $productImage->media_id,
                    'display_order' => $productImage->display_order,
                    'url' => $productImage->media ? $productImage->media->url : null,
                    'filename' => $productImage->media ? $productImage->media->filename : null,
                ];
            });
    }

    /**
     * Scope to get only published products.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at');
    }

    /**
     * Scope to order by display_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }
}
