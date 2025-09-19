<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

use Webkul\Sales\Contracts\SalesReport as SalesReportContract;

class SalesReport extends Model implements SalesReportContract
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'filters',
        'columns',
        'grouping',
        'sorting',
        'date_from',
        'date_to',
        'status',
        'data',
        'file_path',
        'generated_at',
        'error_message',
        'created_by',
        'is_public',
        'shared_with',
        'is_scheduled',
        'schedule_frequency',
        'next_run_at',
        'last_run_at',
    ];

    protected $casts = [
        'filters'     => 'array',
        'columns'     => 'array',
        'grouping'    => 'array',
        'sorting'     => 'array',
        'shared_with' => 'array',
        'date_from'   => 'date',
        'date_to'     => 'date',
        'generated_at' => 'datetime',
        'is_public'   => 'boolean',
        'is_scheduled' => 'boolean',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
    ];

    /**
     * Get the user who created this report.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the report is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the report is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if the report has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the report is accessible by a user.
     */
    public function isAccessibleBy(int $userId): bool
    {
        // Creator can always access
        if ($this->created_by === $userId) {
            return true;
        }

        // Public reports are accessible by all
        if ($this->is_public) {
            return true;
        }

        // Check if user is in shared_with list
        if ($this->shared_with && in_array($userId, $this->shared_with)) {
            return true;
        }

        return false;
    }

    /**
     * Get the file size if file exists.
     */
    public function getFileSizeAttribute(): ?string
    {
        if ($this->file_path && file_exists(storage_path('app/' . $this->file_path))) {
            $bytes = filesize(storage_path('app/' . $this->file_path));
            return $this->formatBytes($bytes);
        }

        return null;
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Scope for completed reports.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for reports accessible by a user.
     */
    public function scopeAccessibleBy($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('created_by', $userId)
              ->orWhere('is_public', true)
              ->orWhereJsonContains('shared_with', $userId);
        });
    }

    /**
     * Scope for scheduled reports.
     */
    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    /**
     * Scope for reports due for execution.
     */
    public function scopeDueForExecution($query)
    {
        return $query->scheduled()
                    ->where('next_run_at', '<=', now());
    }
}
