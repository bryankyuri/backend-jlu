<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceGroup extends Model
{
    protected $fillable = [
        'name',
        'display_order',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'display_order' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer'
    ];

    protected $hidden = [
        'created_by',
        'updated_by'
    ];

    protected $appends = ['items_count'];

    /**
     * Boot the model - Auto-assign display_order and prevent deletion if items exist
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            if (is_null($group->display_order)) {
                $maxOrder = static::max('display_order') ?? 0;
                $group->display_order = $maxOrder + 1;
            }
        });

        static::deleting(function ($group) {
            if ($group->serviceItems()->count() > 0) {
                throw new \Exception('Cannot delete group with existing service items. Please delete or reassign the items first.');
            }
        });
    }

    /**
     * Get items count attribute
     */
    public function getItemsCountAttribute()
    {
        return $this->serviceItems()->count();
    }

    /**
     * Get all service items belonging to this group
     */
    public function serviceItems(): HasMany
    {
        return $this->hasMany(ServiceItem::class, 'service_group_id')->orderBy('display_order');
    }

    /**
     * Get the user who created this group
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this group
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
