<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

use Webkul\Sales\Contracts\SalesPerformance as SalesPerformanceContract;

class SalesPerformance extends Model implements SalesPerformanceContract
{
    protected $table = 'sales_performance';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'entity_name',
        'sales_target_id',
        'parent_performance_id',
        'is_team_aggregate',
        'period_start',
        'period_end',
        'period_type',
        'target_amount',
        'achieved_amount',
        'achievement_percentage',
        'leads_count',
        'won_leads_count',
        'lost_leads_count',
        'conversion_rate',
        'average_deal_size',
        'score',
        'rank',
        'badges',
        'metadata',
        'member_contributions',
        'calculated_at',
        'last_synced_at',
    ];

    protected $casts = [
        'period_start'          => 'date',
        'period_end'            => 'date',
        'target_amount'         => 'decimal:2',
        'achieved_amount'       => 'decimal:2',
        'achievement_percentage' => 'decimal:2',
        'conversion_rate'       => 'decimal:2',
        'average_deal_size'     => 'decimal:2',
        'is_team_aggregate'     => 'boolean',
        'badges'                => 'array',
        'metadata'              => 'array',
        'member_contributions'  => 'array',
        'calculated_at'         => 'datetime',
        'last_synced_at'        => 'datetime',
    ];

    /**
     * Get the sales target this performance is related to.
     */
    public function salesTarget(): BelongsTo
    {
        return $this->belongsTo(SalesTarget::class, 'sales_target_id');
    }

    /**
     * Get the parent performance (for team hierarchy).
     */
    public function parentPerformance(): BelongsTo
    {
        return $this->belongsTo(SalesPerformance::class, 'parent_performance_id');
    }

    /**
     * Get child performances (team members' performance).
     */
    public function childPerformances()
    {
        return $this->hasMany(SalesPerformance::class, 'parent_performance_id');
    }

    /**
     * Get the entity based on entity_type.
     */
    public function entity()
    {
        switch ($this->entity_type) {
            case 'individual':
                return $this->belongsTo(User::class, 'entity_id');
            case 'team':
                return $this->belongsTo(SalesTeam::class, 'entity_id');
            case 'region':
                return $this->belongsTo(SalesRegion::class, 'entity_id');
            default:
                return null;
        }
    }

    /**
     * Calculate achievement percentage.
     */
    public function calculateAchievementPercentage(): void
    {
        if ($this->target_amount > 0) {
            $this->achievement_percentage = min(100, ($this->achieved_amount / $this->target_amount) * 100);
        } else {
            $this->achievement_percentage = 0;
        }
    }

    /**
     * Calculate conversion rate.
     */
    public function calculateConversionRate(): void
    {
        if ($this->leads_count > 0) {
            $this->conversion_rate = ($this->won_leads_count / $this->leads_count) * 100;
        } else {
            $this->conversion_rate = 0;
        }
    }

    /**
     * Calculate average deal size.
     */
    public function calculateAverageDealSize(): void
    {
        if ($this->won_leads_count > 0) {
            $this->average_deal_size = $this->achieved_amount / $this->won_leads_count;
        } else {
            $this->average_deal_size = 0;
        }
    }

    /**
     * Calculate performance score.
     */
    public function calculateScore(): void
    {
        $achievementScore = min(100, $this->achievement_percentage);
        $conversionScore = min(100, $this->conversion_rate * 2); // Weight conversion rate
        $activityScore = min(100, ($this->leads_count / 10) * 10); // 10 leads = 10 points

        $this->score = round(($achievementScore * 0.5) + ($conversionScore * 0.3) + ($activityScore * 0.2));
    }

    /**
     * Scope for performance in a specific period.
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
    }

    /**
     * Scope for performance by entity type.
     */
    public function scopeByEntityType($query, string $type)
    {
        return $query->where('entity_type', $type);
    }

    /**
     * Scope for performance by entity.
     */
    public function scopeByEntity($query, string $type, int $id)
    {
        return $query->where('entity_type', $type)
                    ->where('entity_id', $id);
    }

    /**
     * Scope for performance by period type.
     */
    public function scopeByPeriodType($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }
}
