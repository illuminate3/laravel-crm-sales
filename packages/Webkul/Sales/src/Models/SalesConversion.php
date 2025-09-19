<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;
use Webkul\Lead\Models\Lead;

class SalesConversion extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'sales_target_id',
        'conversion_amount',
        'conversion_date',
        'conversion_type',
        'is_counted',
        'metadata',
    ];

    protected $casts = [
        'conversion_amount' => 'decimal:2',
        'conversion_date' => 'date',
        'is_counted' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the lead this conversion is for.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user (sales person) responsible for this conversion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sales target this conversion contributes to.
     */
    public function salesTarget(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class, 'sales_target_id');
    }

    /**
     * Scope for conversions in a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('conversion_date', [$startDate, $endDate]);
    }

    /**
     * Scope for conversions by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for conversions by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('conversion_type', $type);
    }

    /**
     * Scope for counted conversions only.
     */
    public function scopeCounted($query)
    {
        return $query->where('is_counted', true);
    }

    /**
     * Scope for conversions contributing to a specific target.
     */
    public function scopeForTarget($query, int $targetId)
    {
        return $query->where('sales_target_id', $targetId);
    }

    /**
     * Get total conversion amount for a user in a period.
     */
    public static function getTotalForUser(int $userId, $startDate, $endDate): float
    {
        return static::byUser($userId)
                    ->inDateRange($startDate, $endDate)
                    ->counted()
                    ->sum('conversion_amount') ?? 0;
    }

    /**
     * Get total conversion amount for a target.
     */
    public static function getTotalForTarget(int $targetId): float
    {
        return static::forTarget($targetId)
                    ->counted()
                    ->sum('conversion_amount') ?? 0;
    }

    /**
     * Get conversion count by type for a user.
     */
    public static function getCountByTypeForUser(int $userId, string $type, $startDate, $endDate): int
    {
        return static::byUser($userId)
                    ->byType($type)
                    ->inDateRange($startDate, $endDate)
                    ->counted()
                    ->count();
    }
}
