<?php

namespace Webkul\Sales\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Services\SalesPerformanceCalculationService;
use Webkul\Sales\Models\SalesTarget;
use Webkul\Sales\Models\SalesPerformance;
use Webkul\Sales\Models\SalesConversion;
use Webkul\Lead\Models\Lead;

class RecalculateSalesPerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:recalculate-performance 
                            {--target-id= : Specific target ID to recalculate}
                            {--clean : Clean existing performance data before recalculating}
                            {--create-conversions : Create conversion records from existing won leads}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate sales performance data and ensure team hierarchy consistency';

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
        $this->info('Starting sales performance recalculation...');

        try {
            DB::beginTransaction();

            if ($this->option('clean')) {
                $this->cleanExistingData();
            }

            if ($this->option('create-conversions')) {
                $this->createConversionsFromLeads();
            }

            if ($targetId = $this->option('target-id')) {
                $this->recalculateSpecificTarget($targetId);
            } else {
                $this->recalculateAllTargets();
            }

            $this->ensureTeamHierarchyConsistency();
            $this->updateRankings();

            DB::commit();
            $this->info('Sales performance recalculation completed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during recalculation: ' . $e->getMessage());
            Log::error('Sales performance recalculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Clean existing performance data.
     */
    protected function cleanExistingData(): void
    {
        $this->info('Cleaning existing performance data...');
        
        if ($this->option('create-conversions')) {
            SalesConversion::truncate();
            $this->info('Cleared sales conversions');
        }
        
        SalesPerformance::truncate();
        $this->info('Cleared sales performance records');
        
        // Reset achieved amounts in targets
        SalesTarget::query()->update([
            'achieved_amount' => 0,
            'achieved_new_logos' => 0,
            'achieved_crs_and_renewals_obv' => 0,
            'progress_percentage' => 0,
            'last_calculated_at' => null
        ]);
        $this->info('Reset target achieved amounts');
    }

    /**
     * Create conversion records from existing won leads.
     */
    protected function createConversionsFromLeads(): void
    {
        $this->info('Creating conversion records from won leads...');
        
        $wonLeads = Lead::where('status', 1) // Assuming 1 = won
            ->whereNotNull('user_id')
            ->whereNotNull('lead_value')
            ->where('lead_value', '>', 0)
            ->get();

        $progressBar = $this->output->createProgressBar($wonLeads->count());
        $progressBar->start();

        foreach ($wonLeads as $lead) {
            $this->performanceService->createConversionFromLead($lead);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Created conversions for {$wonLeads->count()} won leads");
    }

    /**
     * Recalculate performance for a specific target.
     */
    protected function recalculateSpecificTarget(int $targetId): void
    {
        $target = SalesTarget::find($targetId);
        
        if (!$target) {
            $this->error("Target with ID {$targetId} not found");
            return;
        }

        $this->info("Recalculating performance for target: {$target->name}");
        $this->performanceService->calculatePerformanceForTarget($target);
        $this->info("Completed recalculation for target {$targetId}");
    }

    /**
     * Recalculate performance for all targets.
     */
    protected function recalculateAllTargets(): void
    {
        $targets = SalesTarget::all();
        $this->info("Recalculating performance for {$targets->count()} targets...");

        $progressBar = $this->output->createProgressBar($targets->count());
        $progressBar->start();

        foreach ($targets as $target) {
            $this->performanceService->calculatePerformanceForTarget($target);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Completed recalculation for all targets");
    }

    /**
     * Ensure team hierarchy consistency.
     */
    protected function ensureTeamHierarchyConsistency(): void
    {
        $this->info('Ensuring team hierarchy consistency...');

        // Find team performances that need to be recalculated
        $teamPerformances = SalesPerformance::where('entity_type', 'team')
            ->where('is_team_aggregate', true)
            ->get();

        foreach ($teamPerformances as $teamPerformance) {
            $this->validateTeamPerformance($teamPerformance);
        }

        $this->info('Team hierarchy consistency check completed');
    }

    /**
     * Validate and fix team performance aggregation.
     */
    protected function validateTeamPerformance(SalesPerformance $teamPerformance): void
    {
        // Get all individual performances that should contribute to this team
        $memberPerformances = SalesPerformance::where('parent_performance_id', $teamPerformance->id)
            ->where('entity_type', 'individual')
            ->get();

        if ($memberPerformances->isEmpty()) {
            return;
        }

        // Recalculate team totals from member contributions
        $totalAchieved = 0;
        $totalLeads = 0;
        $totalWonLeads = 0;
        $totalLostLeads = 0;
        $memberContributions = [];

        foreach ($memberPerformances as $memberPerformance) {
            // Get team member info for contribution percentage
            $teamMember = DB::table('sales_team_members')
                ->where('team_id', $teamPerformance->entity_id)
                ->where('user_id', $memberPerformance->entity_id)
                ->where('is_active', true)
                ->first();

            $contributionPercentage = $teamMember ? $teamMember->contribution_percentage / 100 : 1;
            $contributedAmount = $memberPerformance->achieved_amount * $contributionPercentage;

            $totalAchieved += $contributedAmount;
            $totalLeads += $memberPerformance->leads_count;
            $totalWonLeads += $memberPerformance->won_leads_count;
            $totalLostLeads += $memberPerformance->lost_leads_count;

            $memberContributions[] = [
                'user_id' => $memberPerformance->entity_id,
                'user_name' => $memberPerformance->entity_name,
                'role_name' => $teamMember->role_name ?? 'Unknown',
                'achieved_amount' => $memberPerformance->achieved_amount,
                'contributed_amount' => $contributedAmount,
                'contribution_percentage' => $contributionPercentage * 100,
                'leads_count' => $memberPerformance->leads_count,
                'won_leads_count' => $memberPerformance->won_leads_count,
            ];
        }

        // Update team performance if there are discrepancies
        if (abs($teamPerformance->achieved_amount - $totalAchieved) > 0.01) {
            $teamPerformance->update([
                'achieved_amount' => $totalAchieved,
                'leads_count' => $totalLeads,
                'won_leads_count' => $totalWonLeads,
                'lost_leads_count' => $totalLostLeads,
                'member_contributions' => $memberContributions,
                'last_synced_at' => now(),
            ]);

            $teamPerformance->calculateAchievementPercentage();
            $teamPerformance->calculateConversionRate();
            $teamPerformance->calculateAverageDealSize();
            $teamPerformance->calculateScore();
            $teamPerformance->save();

            $this->info("Fixed team performance for team {$teamPerformance->entity_name}");
        }
    }

    /**
     * Update performance rankings.
     */
    protected function updateRankings(): void
    {
        $this->info('Updating performance rankings...');
        
        // Update individual rankings
        $individualPerformances = SalesPerformance::where('entity_type', 'individual')
            ->orderBy('score', 'desc')
            ->orderBy('achievement_percentage', 'desc')
            ->get();

        $rank = 1;
        foreach ($individualPerformances as $performance) {
            $performance->update(['rank' => $rank]);
            $rank++;
        }

        // Update team rankings
        $teamPerformances = SalesPerformance::where('entity_type', 'team')
            ->where('is_team_aggregate', true)
            ->orderBy('score', 'desc')
            ->orderBy('achievement_percentage', 'desc')
            ->get();

        $rank = 1;
        foreach ($teamPerformances as $performance) {
            $performance->update(['rank' => $rank]);
            $rank++;
        }

        $this->info('Performance rankings updated');
    }
}
