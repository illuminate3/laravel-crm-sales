<?php

namespace Webkul\Sales\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\SalesTarget;
use Webkul\Sales\Models\SalesPerformance;
use Webkul\Sales\Models\SalesConversion;
use Webkul\Sales\Models\SalesTeamMember;
use Webkul\Sales\Models\SalesTargetAssignment;
use Webkul\Sales\Services\SalesPerformanceCalculationService;
use Webkul\Lead\Models\Lead;
use Webkul\User\Models\User;

class MigrateSalesDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:migrate-data 
                            {--clean-duplicates : Remove duplicate performance records}
                            {--fix-team-roles : Update team member role names from user roles}
                            {--create-assignments : Create target assignments from existing targets}
                            {--create-conversions : Create conversion records from won leads}
                            {--recalculate : Recalculate all performance data after migration}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing sales data to new schema and clean up inconsistencies';

    /**
     * Sales performance calculation service.
     */
    protected $performanceService;

    /**
     * Create a new command instance.
     */
    public function __construct(SalesPerformanceCalculationService $performanceService)
    {
        parent::__construct();
        $this->performanceService = $performanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting sales data migration...');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            if (!$isDryRun) {
                DB::beginTransaction();
            }

            $this->migrateData($isDryRun);

            if (!$isDryRun) {
                DB::commit();
                $this->info('Sales data migration completed successfully!');
            } else {
                $this->info('Dry run completed - no changes were made');
            }

        } catch (\Exception $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            $this->error('Error during migration: ' . $e->getMessage());
            Log::error('Sales data migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Perform the data migration.
     */
    protected function migrateData(bool $isDryRun): void
    {
        if ($this->option('clean-duplicates')) {
            $this->cleanDuplicatePerformanceRecords($isDryRun);
        }

        if ($this->option('fix-team-roles')) {
            $this->fixTeamMemberRoles($isDryRun);
        }

        if ($this->option('create-assignments')) {
            $this->createTargetAssignments($isDryRun);
        }

        if ($this->option('create-conversions')) {
            $this->createConversionsFromLeads($isDryRun);
        }

        if ($this->option('recalculate')) {
            $this->recalculatePerformanceData($isDryRun);
        }
    }

    /**
     * Clean duplicate performance records.
     */
    protected function cleanDuplicatePerformanceRecords(bool $isDryRun): void
    {
        $this->info('Cleaning duplicate performance records...');

        // Find duplicates based on entity_type, entity_id, period_start, period_end, period_type
        $duplicates = DB::table('sales_performance')
            ->select([
                'entity_type',
                'entity_id', 
                'period_start',
                'period_end',
                'period_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('MIN(id) as keep_id'),
                DB::raw('GROUP_CONCAT(id) as all_ids')
            ])
            ->groupBy(['entity_type', 'entity_id', 'period_start', 'period_end', 'period_type'])
            ->having('count', '>', 1)
            ->get();

        $totalDuplicates = 0;
        foreach ($duplicates as $duplicate) {
            $allIds = explode(',', $duplicate->all_ids);
            $idsToDelete = array_filter($allIds, fn($id) => $id != $duplicate->keep_id);
            $totalDuplicates += count($idsToDelete);

            if (!$isDryRun && !empty($idsToDelete)) {
                SalesPerformance::whereIn('id', $idsToDelete)->delete();
            }
        }

        $this->info("Found and " . ($isDryRun ? 'would remove' : 'removed') . " {$totalDuplicates} duplicate performance records");
    }

    /**
     * Fix team member role names.
     */
    protected function fixTeamMemberRoles(bool $isDryRun): void
    {
        $this->info('Fixing team member role names...');

        $membersWithoutRoles = SalesTeamMember::whereNull('role_name')
            ->orWhere('role_name', '')
            ->with(['user.role'])
            ->get();

        $updated = 0;
        foreach ($membersWithoutRoles as $member) {
            if ($member->user && $member->user->role) {
                if (!$isDryRun) {
                    $member->update(['role_name' => $member->user->role->name]);
                }
                $updated++;
            }
        }

        $this->info("Fixed role names for {$updated} team members");
    }

    /**
     * Create target assignments from existing targets.
     */
    protected function createTargetAssignments(bool $isDryRun): void
    {
        $this->info('Creating target assignments from existing targets...');

        $targets = SalesTarget::whereDoesntHave('assignments')->get();
        $created = 0;

        foreach ($targets as $target) {
            $assignmentData = [
                'sales_target_id' => $target->id,
                'assignee_type' => $target->assignee_type,
                'assignee_id' => $target->assignee_id,
                'assignee_name' => $target->assignee_name,
                'allocated_amount' => $target->target_amount,
                'achieved_amount' => $target->achieved_amount,
                'allocation_percentage' => 100.00,
                'is_primary' => true,
            ];

            if (!$isDryRun) {
                SalesTargetAssignment::create($assignmentData);
            }
            $created++;
        }

        $this->info("Created {$created} target assignments");
    }

    /**
     * Create conversion records from existing won leads.
     */
    protected function createConversionsFromLeads(bool $isDryRun): void
    {
        $this->info('Creating conversion records from won leads...');

        // Get won leads that don't have conversion records
        $wonLeads = Lead::where('status', 1) // Assuming 1 = won
            ->whereNotNull('user_id')
            ->whereNotNull('lead_value')
            ->where('lead_value', '>', 0)
            ->whereDoesntHave('conversions')
            ->get();

        $progressBar = $this->output->createProgressBar($wonLeads->count());
        $progressBar->start();

        $created = 0;
        foreach ($wonLeads as $lead) {
            if (!$isDryRun) {
                $conversion = $this->performanceService->createConversionFromLead($lead);
                if ($conversion) {
                    $created++;
                }
            } else {
                $created++; // Count what would be created
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Created {$created} conversion records from won leads");
    }

    /**
     * Recalculate all performance data.
     */
    protected function recalculatePerformanceData(bool $isDryRun): void
    {
        if ($isDryRun) {
            $this->info('Would recalculate all performance data');
            return;
        }

        $this->info('Recalculating all performance data...');
        $this->performanceService->calculateAllPerformances();
        $this->info('Performance data recalculation completed');
    }

    /**
     * Validate data integrity after migration.
     */
    protected function validateDataIntegrity(): array
    {
        $issues = [];

        // Check for orphaned performance records
        $orphanedPerformances = SalesPerformance::whereNotNull('sales_target_id')
            ->whereDoesntHave('salesTarget')
            ->count();
        
        if ($orphanedPerformances > 0) {
            $issues[] = "Found {$orphanedPerformances} orphaned performance records";
        }

        // Check for team performances without member contributions
        $teamPerformancesWithoutMembers = SalesPerformance::where('entity_type', 'team')
            ->where('is_team_aggregate', true)
            ->where(function($q) {
                $q->whereNull('member_contributions')
                  ->orWhereRaw('JSON_LENGTH(member_contributions) = 0');
            })
            ->count();

        if ($teamPerformancesWithoutMembers > 0) {
            $issues[] = "Found {$teamPerformancesWithoutMembers} team performances without member contributions";
        }

        // Check for inconsistent target amounts
        $inconsistentTargets = SalesTarget::whereRaw('achieved_amount > target_amount * 1.5')
            ->count();

        if ($inconsistentTargets > 0) {
            $issues[] = "Found {$inconsistentTargets} targets with suspiciously high achievement amounts";
        }

        return $issues;
    }
}
