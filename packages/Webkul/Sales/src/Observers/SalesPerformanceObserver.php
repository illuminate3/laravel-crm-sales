<?php

namespace Webkul\Sales\Observers;

use Webkul\Sales\Models\SalesPerformance;
use Webkul\Sales\Models\SalesTeamMember;
use Webkul\Sales\Services\SalesPerformanceCalculationService;
use Illuminate\Support\Facades\Log;

class SalesPerformanceObserver
{
    protected $performanceService;

    public function __construct(SalesPerformanceCalculationService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Handle the SalesPerformance "updated" event.
     */
    public function updated(SalesPerformance $salesPerformance): void
    {
        // Only trigger team updates for individual performances
        if ($salesPerformance->entity_type === 'individual' && !$salesPerformance->is_team_aggregate) {
            $this->updateRelatedTeamPerformances($salesPerformance);
        }
    }

    /**
     * Handle the SalesPerformance "created" event.
     */
    public function created(SalesPerformance $salesPerformance): void
    {
        // Only trigger team updates for individual performances
        if ($salesPerformance->entity_type === 'individual' && !$salesPerformance->is_team_aggregate) {
            $this->updateRelatedTeamPerformances($salesPerformance);
        }
    }

    /**
     * Update team performances when an individual's performance changes.
     */
    protected function updateRelatedTeamPerformances(SalesPerformance $individualPerformance): void
    {
        try {
            // Find all teams this user belongs to
            $teamMemberships = SalesTeamMember::where('user_id', $individualPerformance->entity_id)
                ->where('is_active', true)
                ->with('team')
                ->get();

            foreach ($teamMemberships as $membership) {
                $this->updateTeamPerformanceForMember($membership, $individualPerformance);
            }

        } catch (\Exception $e) {
            Log::error('Failed to update team performances for individual performance', [
                'performance_id' => $individualPerformance->id,
                'user_id' => $individualPerformance->entity_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update team performance for a specific team membership.
     */
    protected function updateTeamPerformanceForMember(SalesTeamMember $membership, SalesPerformance $individualPerformance): void
    {
        $team = $membership->team;
        
        // Find team performance record for the same period
        $teamPerformance = SalesPerformance::where('entity_type', 'team')
            ->where('entity_id', $team->id)
            ->where('period_start', $individualPerformance->period_start)
            ->where('period_end', $individualPerformance->period_end)
            ->where('period_type', $individualPerformance->period_type)
            ->where('is_team_aggregate', true)
            ->first();

        if (!$teamPerformance) {
            // Create team performance if it doesn't exist
            $teamPerformance = $this->createTeamPerformance($team, $individualPerformance);
        }

        // Recalculate team metrics
        $this->recalculateTeamMetrics($teamPerformance, $team);
    }

    /**
     * Create a new team performance record.
     */
    protected function createTeamPerformance($team, SalesPerformance $referencePerformance): SalesPerformance
    {
        return SalesPerformance::create([
            'entity_type' => 'team',
            'entity_id' => $team->id,
            'entity_name' => $team->name,
            'sales_target_id' => $referencePerformance->sales_target_id,
            'is_team_aggregate' => true,
            'period_start' => $referencePerformance->period_start,
            'period_end' => $referencePerformance->period_end,
            'period_type' => $referencePerformance->period_type,
            'target_amount' => 0, // Will be calculated
            'achieved_amount' => 0, // Will be calculated
            'calculated_at' => now(),
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Recalculate team metrics from member performances.
     */
    protected function recalculateTeamMetrics(SalesPerformance $teamPerformance, $team): void
    {
        // Get all active team members
        $activeMembers = $team->members()->wherePivot('is_active', true)->get();
        
        $totalAchieved = 0;
        $totalTarget = 0;
        $totalLeads = 0;
        $totalWonLeads = 0;
        $totalLostLeads = 0;
        $memberContributions = [];

        foreach ($activeMembers as $member) {
            // Get member's performance for the same period
            $memberPerformance = SalesPerformance::where('entity_type', 'individual')
                ->where('entity_id', $member->id)
                ->where('period_start', $teamPerformance->period_start)
                ->where('period_end', $teamPerformance->period_end)
                ->where('period_type', $teamPerformance->period_type)
                ->first();

            if (!$memberPerformance) {
                continue;
            }

            // Get team member info for contribution percentage
            $teamMember = SalesTeamMember::where('team_id', $team->id)
                ->where('user_id', $member->id)
                ->where('is_active', true)
                ->first();

            $contributionPercentage = $teamMember ? $teamMember->contribution_percentage / 100 : 1;
            $contributedAmount = $memberPerformance->achieved_amount * $contributionPercentage;
            $contributedTarget = $memberPerformance->target_amount * $contributionPercentage;

            $totalAchieved += $contributedAmount;
            $totalTarget += $contributedTarget;
            $totalLeads += $memberPerformance->leads_count;
            $totalWonLeads += $memberPerformance->won_leads_count;
            $totalLostLeads += $memberPerformance->lost_leads_count;

            $memberContributions[] = [
                'user_id' => $member->id,
                'user_name' => $member->name,
                'role_name' => $teamMember->role_name ?? 'Unknown',
                'achieved_amount' => $memberPerformance->achieved_amount,
                'contributed_amount' => $contributedAmount,
                'contribution_percentage' => $contributionPercentage * 100,
                'leads_count' => $memberPerformance->leads_count,
                'won_leads_count' => $memberPerformance->won_leads_count,
            ];

            // Link member performance to team performance
            $memberPerformance->update(['parent_performance_id' => $teamPerformance->id]);
        }

        // Update team performance with aggregated data
        $teamPerformance->update([
            'target_amount' => $totalTarget,
            'achieved_amount' => $totalAchieved,
            'leads_count' => $totalLeads,
            'won_leads_count' => $totalWonLeads,
            'lost_leads_count' => $totalLostLeads,
            'member_contributions' => $memberContributions,
            'calculated_at' => now(),
            'last_synced_at' => now(),
        ]);

        // Calculate derived metrics
        $teamPerformance->calculateAchievementPercentage();
        $teamPerformance->calculateConversionRate();
        $teamPerformance->calculateAverageDealSize();
        $teamPerformance->calculateScore();
        $teamPerformance->save();

        Log::info("Updated team performance for team {$team->name}", [
            'team_id' => $team->id,
            'achieved_amount' => $totalAchieved,
            'target_amount' => $totalTarget,
            'member_count' => count($memberContributions)
        ]);
    }
}
