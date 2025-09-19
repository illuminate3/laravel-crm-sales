<?php

namespace Webkul\Sales\Observers;

use Webkul\Sales\Models\SalesTarget;
use Webkul\Sales\Services\SalesPerformanceCalculationService;
use Illuminate\Support\Facades\Log;

class SalesTargetObserver
{
    protected $performanceService;

    public function __construct(SalesPerformanceCalculationService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Handle the SalesTarget "updated" event.
     */
    public function updated(SalesTarget $salesTarget): void
    {
        // Check if important fields were changed
        $importantFields = [
            'target_amount',
            'target_for_new_logo',
            'crs_and_renewals_obv',
            'assignee_type',
            'assignee_id',
            'start_date',
            'end_date',
            'status'
        ];

        $hasImportantChanges = false;
        foreach ($importantFields as $field) {
            if ($salesTarget->isDirty($field)) {
                $hasImportantChanges = true;
                break;
            }
        }

        if ($hasImportantChanges) {
            $this->syncPerformanceWithTarget($salesTarget);
        }
    }

    /**
     * Handle the SalesTarget "created" event.
     */
    public function created(SalesTarget $salesTarget): void
    {
        $this->syncPerformanceWithTarget($salesTarget);
    }

    /**
     * Handle the SalesTarget "deleted" event.
     */
    public function deleted(SalesTarget $salesTarget): void
    {
        // Clean up related performance records
        $salesTarget->performances()->delete();
        
        Log::info("Cleaned up performance records for deleted target", [
            'target_id' => $salesTarget->id,
            'target_name' => $salesTarget->name
        ]);
    }

    /**
     * Sync performance data with target changes.
     */
    protected function syncPerformanceWithTarget(SalesTarget $salesTarget): void
    {
        try {
            Log::info("Syncing performance with target changes", [
                'target_id' => $salesTarget->id,
                'target_name' => $salesTarget->name,
                'assignee_type' => $salesTarget->assignee_type,
                'assignee_id' => $salesTarget->assignee_id
            ]);

            $this->performanceService->syncPerformanceWithTarget($salesTarget);

        } catch (\Exception $e) {
            Log::error('Failed to sync performance with target changes', [
                'target_id' => $salesTarget->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
