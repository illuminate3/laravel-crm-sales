<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

class SalesTargetAssignment extends Model
{
    protected $fillable = [
        'sales_target_id',
        'assignee_type',
        'assignee_id',
        'assignee_name',
        'allocated_amount',
        'achieved_amount',
        'allocation_percentage',
        'is_primary',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'achieved_amount' => 'decimal:2',
        'allocation_percentage' => 'decimal:2',
        'is_primary' => 'boolean',
    ];

    /**
     * Get the sales target this assignment belongs to.
     */
    public function salesTarget(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class, 'sales_target_id');
    }

    /**
     * Get the assignee based on assignee_type.
     */
    public function assignee()
    {
        switch ($this->assignee_type) {
            case 'individual':
                return $this->belongsTo(User::class, 'assignee_id');
            case 'team':
                return $this->belongsTo(SalesTeam::class, 'assignee_id');
            case 'region':
                return $this->belongsTo(SalesRegion::class, 'assignee_id');
            default:
                return null;
        }
    }

    /**
     * Calculate achievement percentage.
     */
    public function calculateAchievementPercentage(): float
    {
        if ($this->allocated_amount > 0) {
            return min(100, ($this->achieved_amount / $this->allocated_amount) * 100);
        }
        return 0;
    }

    /**
     * Update achievement amount and sync with parent target.
     */
    public function updateAchievement(float $amount): void
    {
        $this->achieved_amount = $amount;
        $this->save();

        // Update parent sales target
        $this->salesTarget->updateAchievedAmount();
    }

    /**
     * Scope for primary assignments.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for assignments by type.
     */
    public function scopeByAssigneeType($query, string $type)
    {
        return $query->where('assignee_type', $type);
    }

    /**
     * Scope for assignments by assignee.
     */
    public function scopeByAssignee($query, string $type, int $id)
    {
        return $query->where('assignee_type', $type)
                    ->where('assignee_id', $id);
    }
}
