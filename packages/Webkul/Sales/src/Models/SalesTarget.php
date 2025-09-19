<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\User\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webkul\Sales\Contracts\SalesTarget as SalesTargetContract;
use Webkul\Sales\Database\Factories\SalesTargetFactory;

class SalesTarget extends Model implements SalesTargetContract
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'target_amount',
        'target_for_new_logo',
        'crs_and_renewals_obv',
        'financial_year',
        'quarter',
        'achieved_amount',
        'achieved_new_logos',
        'achieved_crs_and_renewals_obv',
        'assignee_type',
        'assignee_id',
        'assignee_name',
        'start_date',
        'end_date',
        'period_type',
        'status',
        'progress_percentage',
        'last_calculated_at',
        'notes',
        'attachments',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'target_amount'                 => 'decimal:2',
        'crs_and_renewals_obv'          => 'decimal:2',
        'achieved_amount'               => 'decimal:2',
        'achieved_crs_and_renewals_obv' => 'decimal:2',
        'progress_percentage'           => 'decimal:2',
        'start_date'          => 'date',
        'end_date'            => 'date',
        'last_calculated_at'  => 'datetime',
        'attachments'         => 'array',
        'metadata'            => 'array',
    ];

    /**
     * Get the user who created this target.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this target.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the adjustments for this target.
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(SalesTargetAdjustment::class);
    }

    /**
     * Get the assignments for this target.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(SalesTargetAssignment::class, 'sales_target_id');
    }

    /**
     * Get the conversions contributing to this target.
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(SalesConversion::class, 'sales_target_id');
    }

    /**
     * Get the performance records for this target.
     */
    public function performances(): HasMany
    {
        return $this->hasMany(SalesPerformance::class, 'sales_target_id');
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
     * Calculate and update achieved amount from conversions.
     */
    public function updateAchievedAmount(): void
    {
        $totalFromConversions = $this->conversions()->counted()->sum('conversion_amount') ?? 0;
        $totalFromAssignments = $this->assignments()->sum('achieved_amount') ?? 0;

        // Use the higher of the two (conversions should be the source of truth)
        $this->achieved_amount = max($totalFromConversions, $totalFromAssignments);
        $this->updateProgress();
        $this->save();
    }

    /**
     * Calculate and update progress percentage.
     */
    public function updateProgress(): void
    {
        if ($this->target_amount > 0) {
            $this->progress_percentage = min(100, ($this->achieved_amount / $this->target_amount) * 100);
        } else {
            $this->progress_percentage = 0;
        }

        $this->last_calculated_at = now();
    }

    /**
     * Check if target is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->start_date <= now()->toDateString() && 
               $this->end_date >= now()->toDateString();
    }

    /**
     * Check if target is achieved.
     */
    public function isAchieved(): bool
    {
        return $this->progress_percentage >= 100;
    }

    /**
     * Scope for active targets.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('start_date', '<=', now()->toDateString())
                    ->where('end_date', '>=', now()->toDateString());
    }

    /**
     * Scope for targets by assignee.
     */
    public function scopeForAssignee($query, string $type, int $id)
    {
        return $query->where('assignee_type', $type)
                    ->where('assignee_id', $id);
    }

    protected static function newFactory()
    {
        return SalesTargetFactory::new();
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saving(function ($target) {
            $target->updateProgress();
        });
    }
}
