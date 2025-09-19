<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

class UserAchievement extends Model
{
    protected $fillable = [
        'user_id',
        'achievement_id',
        'earned_at',
        'points_awarded',
        'criteria_met',
        'related_target_id',
        'period_type',
        'period_start',
        'period_end',
        'metadata',
    ];

    protected $casts = [
        'earned_at'     => 'datetime',
        'criteria_met'  => 'array',
        'metadata'      => 'array',
        'period_start'  => 'date',
        'period_end'    => 'date',
    ];

    /**
     * Get the user who earned this achievement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the achievement that was earned.
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(SalesAchievement::class, 'achievement_id');
    }

    /**
     * Get the related sales target if applicable.
     */
    public function relatedTarget(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class, 'related_target_id');
    }

    /**
     * Scope for achievements earned in a specific period.
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('earned_at', [$startDate, $endDate]);
    }

    /**
     * Scope for achievements by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
