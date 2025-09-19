<?php

namespace Webkul\Sales\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Services\SalesPerformanceCalculationService;
use Webkul\Sales\Models\SalesTarget;

class SyncSalesPerformanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $targetId;
    protected $forceRecalculation;

    /**
     * Create a new job instance.
     */
    public function __construct(int $targetId = null, bool $forceRecalculation = false)
    {
        $this->targetId = $targetId;
        $this->forceRecalculation = $forceRecalculation;
    }

    /**
     * Execute the job.
     */
    public function handle(SalesPerformanceCalculationService $performanceService): void
    {
        try {
            if ($this->targetId) {
                $this->syncSpecificTarget($performanceService);
            } else {
                $this->syncAllActiveTargets($performanceService);
            }

        } catch (\Exception $e) {
            Log::error('Sales performance sync job failed', [
                'target_id' => $this->targetId,
                'force_recalculation' => $this->forceRecalculation,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Sync performance for a specific target.
     */
    protected function syncSpecificTarget(SalesPerformanceCalculationService $performanceService): void
    {
        $target = SalesTarget::find($this->targetId);
        
        if (!$target) {
            Log::warning("Target not found for sync job", ['target_id' => $this->targetId]);
            return;
        }

        Log::info("Syncing performance for specific target", [
            'target_id' => $target->id,
            'target_name' => $target->name
        ]);

        $performanceService->calculatePerformanceForTarget($target);
    }

    /**
     * Sync performance for all active targets.
     */
    protected function syncAllActiveTargets(SalesPerformanceCalculationService $performanceService): void
    {
        Log::info("Starting sync for all active targets");

        $activeTargets = SalesTarget::active()->get();
        
        foreach ($activeTargets as $target) {
            try {
                $performanceService->calculatePerformanceForTarget($target);
            } catch (\Exception $e) {
                Log::error("Failed to sync target performance", [
                    'target_id' => $target->id,
                    'error' => $e->getMessage()
                ]);
                // Continue with other targets
            }
        }

        Log::info("Completed sync for all active targets", [
            'targets_processed' => $activeTargets->count()
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Sales performance sync job failed permanently', [
            'target_id' => $this->targetId,
            'force_recalculation' => $this->forceRecalculation,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
