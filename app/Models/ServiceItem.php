<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceItem extends Model
{
    protected $fillable = [
        'service_group_id',
        'title',
        'description',
        'status',
        'display_order',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'service_group_id' => 'integer',
        'display_order' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer'
    ];

    protected $hidden = [
        'created_by',
        'updated_by'
    ];

    /**
     * Boot the model - Auto-assign display_order scoped by group
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (is_null($item->display_order)) {
                $maxOrder = static::where('service_group_id', $item->service_group_id)
                                  ->max('display_order') ?? 0;
                $item->display_order = $maxOrder + 1;
            }
        });
    }

    /**
     * Get the service group this item belongs to
     */
    public function serviceGroup(): BelongsTo
    {
        return $this->belongsTo(ServiceGroup::class, 'service_group_id');
    }

    /**
     * Get the user who created this item
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this item
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
