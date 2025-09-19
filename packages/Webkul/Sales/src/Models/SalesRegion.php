<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\User\Models\User;

class SalesRegion extends Model
{
    protected $fillable = [
        'name',
        'description',
        'manager_id',
        'territories',
        'is_active',
    ];

    protected $casts = [
        'territories' => 'array',
        'is_active'   => 'boolean',
    ];

    /**
     * Get the manager of this region.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the sales targets for this region.
     */
    public function salesTargets(): HasMany
    {
        return $this->hasMany(SalesTarget::class, 'assignee_id')
                    ->where('assignee_type', 'region');
    }

    /**
     * Scope for active regions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
