<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

class SalesTargetAdjustment extends Model
{
    protected $fillable = [
        'sales_target_id',
        'adjustment_type',
        'old_value',
        'new_value',
        'reason',
        'adjusted_by',
        'adjusted_at',
    ];

    protected $casts = [
        'old_value'    => 'array',
        'new_value'    => 'array',
        'adjusted_at'  => 'datetime',
    ];

    /**
     * Get the sales target this adjustment belongs to.
     */
    public function salesTarget(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class);
    }

    /**
     * Get the user who made this adjustment.
     */
    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
