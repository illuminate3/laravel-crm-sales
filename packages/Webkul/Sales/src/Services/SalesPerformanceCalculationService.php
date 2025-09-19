<?php

namespace Webkul\Sales\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\SalesTarget;
use Webkul\Sales\Models\SalesPerformance;
use Webkul\Sales\Models\SalesConversion;
use Webkul\Sales\Models\SalesTeam;
use Webkul\Sales\Models\SalesTeamMember;
use Webkul\User\Models\User;
use Webkul\Lead\Models\Lead;

class SalesPerformanceCalculationService
{
    /**
     * Calculate and update performance for all active targets.
     */
    public function calculateAllPerformances(): void
    {
        $activeTargets = SalesTarget::active()->get();

        foreach ($activeTargets as $target) {
            $this->calculatePerformanceForTarget($target);
        }

        $this->updateRankings();
    }

    /**
     * Calculate performance for a specific target.
     */
    public function calculatePerformanceForTarget(SalesTarget $target): void
    {
        Log::info("Calculating performance for target: {$target->id} - {$target->name}");

        // Update target's achieved amount from conversions
        $target->updateAchievedAmount();

        // Calculate performance based on assignee type
        switch ($target->assignee_type) {
            case 'individual':
                $this->calculateIndividualPerformance($target);
                break;
            case 'team':
                $this->calculateTeamPerformance($target);
                break;
            case 'region':
                $this->calculateRegionPerformance($target);
                break;
        }
    }

    /**
     * Calculate individual performance.
     */
    protected function calculateIndividualPerformance(SalesTarget $target): void
    {
        $user = User::find($target->assignee_id);
        if (!$user) {
            Log::warning("User not found for target {$target->id}");
            return;
        }

        // Get or create performance record
        $performance = SalesPerformance::updateOrCreate([
            'entity_type' => 'individual',
            'entity_id' => $target->assignee_id,
            'sales_target_id' => $target->id,
            'period_start' => $target->start_date,
            'period_end' => $target->end_date,
            'period_type' => $target->period_type,
        ], [
            'entity_name' => $target->assignee_name,
            'is_team_aggregate' => false,
        ]);

        // Calculate metrics from conversions and leads
        $this->calculateIndividualMetrics($performance, $user, $target);

        // Update team performance if user is part of a team
        $this->updateTeamPerformanceForMember($user, $target);
    }

    /**
     * Calculate team performance.
     */
    protected function calculateTeamPerformance(SalesTarget $target): void
    {
        $team = SalesTeam::find($target->assignee_id);
        if (!$team) {
            Log::warning("Team not found for target {$target->id}");
            return;
        }

        // Get or create team performance record
        $teamPerformance = SalesPerformance::updateOrCreate([
            'entity_type' => 'team',
            'entity_id' => $target->assignee_id,
            'sales_target_id' => $target->id,
            'period_start' => $target->start_date,
            'period_end' => $target->end_date,
            'period_type' => $target->period_type,
        ], [
            'entity_name' => $target->assignee_name,
            'is_team_aggregate' => true,
        ]);

        // Calculate team metrics by aggregating member performance
        $this->calculateTeamMetrics($teamPerformance, $team, $target);

        // Calculate individual performance for each team member
        $this->calculateTeamMemberPerformances($team, $target, $teamPerformance);
    }

    /**
     * Calculate individual metrics for a user.
     */
    protected function calculateIndividualMetrics(SalesPerformance $performance, User $user, SalesTarget $target): void
    {
        $startDate = $target->start_date;
        $endDate = $target->end_date;

        // Get conversions for this user in the target period
        $conversions = SalesConversion::byUser($user->id)
            ->inDateRange($startDate, $endDate)
            ->counted()
            ->get();

        // Calculate achieved amount
        $achievedAmount = $conversions->sum('conversion_amount');

        // Get leads data for additional metrics
        $leads = Lead::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $wonLeads = $leads->where('status', 1); // Assuming 1 = won
        $lostLeads = $leads->where('status', 0); // Assuming 0 = lost

        // Update performance record
        $performance->update([
            'target_amount' => $target->target_amount,
            'achieved_amount' => $achievedAmount,
            'leads_count' => $leads->count(),
            'won_leads_count' => $wonLeads->count(),
            'lost_leads_count' => $lostLeads->count(),
            'calculated_at' => now(),
            'last_synced_at' => now(),
        ]);

        // Calculate derived metrics
        $performance->calculateAchievementPercentage();
        $performance->calculateConversionRate();
        $performance->calculateAverageDealSize();
        $performance->calculateScore();
        $performance->save();

        Log::info("Updated individual performance for user {$user->id}: {$achievedAmount}/{$target->target_amount}");
    }

    /**
     * Calculate team metrics by aggregating member performance.
     */
    protected function calculateTeamMetrics(SalesPerformance $teamPerformance, SalesTeam $team, SalesTarget $target): void
    {
        $activeMembers = $team->members()->wherePivot('is_active', true)->get();
        $memberContributions = [];
        
        $totalAchieved = 0;
        $totalLeads = 0;
        $totalWonLeads = 0;
        $totalLostLeads = 0;

        foreach ($activeMembers as $member) {
            $memberConversions = SalesConversion::byUser($member->id)
                ->inDateRange($target->start_date, $target->end_date)
                ->counted()
                ->sum('conversion_amount');

            $memberLeads = Lead::where('user_id', $member->id)
                ->whereBetween('created_at', [$target->start_date, $target->end_date])
                ->get();

            $memberWonLeads = $memberLeads->where('status', 1)->count();
            $memberLostLeads = $memberLeads->where('status', 0)->count();

            // Apply contribution percentage from team membership
            $teamMember = SalesTeamMember::where('team_id', $team->id)
                ->where('user_id', $member->id)
                ->first();
            
            $contributionPercentage = $teamMember ? $teamMember->contribution_percentage / 100 : 1;
            
            $contributedAmount = $memberConversions * $contributionPercentage;
            $totalAchieved += $contributedAmount;
            $totalLeads += $memberLeads->count();
            $totalWonLeads += $memberWonLeads;
            $totalLostLeads += $memberLostLeads;

            $memberContributions[] = [
                'user_id' => $member->id,
                'user_name' => $member->name,
                'role_name' => $teamMember->role_name ?? 'Unknown',
                'achieved_amount' => $memberConversions,
                'contributed_amount' => $contributedAmount,
                'contribution_percentage' => $contributionPercentage * 100,
                'leads_count' => $memberLeads->count(),
                'won_leads_count' => $memberWonLeads,
            ];
        }

        // Update team performance
        $teamPerformance->update([
            'target_amount' => $target->target_amount,
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

        Log::info("Updated team performance for team {$team->id}: {$totalAchieved}/{$target->target_amount}");
    }

    /**
     * Calculate individual performance for team members and link to team performance.
     */
    protected function calculateTeamMemberPerformances(SalesTeam $team, SalesTarget $target, SalesPerformance $teamPerformance): void
    {
        $activeMembers = $team->members()->wherePivot('is_active', true)->get();

        foreach ($activeMembers as $member) {
            $memberPerformance = SalesPerformance::updateOrCreate([
                'entity_type' => 'individual',
                'entity_id' => $member->id,
                'period_start' => $target->start_date,
                'period_end' => $target->end_date,
                'period_type' => $target->period_type,
            ], [
                'entity_name' => $member->name,
                'sales_target_id' => $target->id,
                'parent_performance_id' => $teamPerformance->id,
                'is_team_aggregate' => false,
            ]);

            $this->calculateIndividualMetrics($memberPerformance, $member, $target);
        }
    }

    /**
     * Update team performance when a member's performance changes.
     */
    protected function updateTeamPerformanceForMember(User $user, SalesTarget $target): void
    {
        $teamMemberships = SalesTeamMember::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('team')
            ->get();

        foreach ($teamMemberships as $membership) {
            $team = $membership->team;
            
            // Find team targets that overlap with this target period
            $teamTargets = SalesTarget::where('assignee_type', 'team')
                ->where('assignee_id', $team->id)
                ->where('start_date', '<=', $target->end_date)
                ->where('end_date', '>=', $target->start_date)
                ->get();

            foreach ($teamTargets as $teamTarget) {
                $this->calculateTeamPerformance($teamTarget);
            }
        }
    }

    /**
     * Calculate region performance (placeholder for future implementation).
     */
    protected function calculateRegionPerformance(SalesTarget $target): void
    {
        // TODO: Implement region performance calculation
        Log::info("Region performance calculation not yet implemented for target {$target->id}");
    }

    /**
     * Update performance rankings.
     */
    protected function updateRankings(): void
    {
        // Update individual rankings
        $this->updateRankingsByType('individual');
        
        // Update team rankings
        $this->updateRankingsByType('team');
    }

    /**
     * Update rankings by entity type.
     */
    protected function updateRankingsByType(string $entityType): void
    {
        $performances = SalesPerformance::where('entity_type', $entityType)
            ->where('period_start', '>=', now()->subMonths(3)) // Recent performances only
            ->orderBy('score', 'desc')
            ->orderBy('achievement_percentage', 'desc')
            ->get();

        $rank = 1;
        foreach ($performances as $performance) {
            $performance->update(['rank' => $rank]);
            $rank++;
        }
    }

    /**
     * Sync performance with target updates.
     */
    public function syncPerformanceWithTarget(SalesTarget $target): void
    {
        Log::info("Syncing performance with target updates for target {$target->id}");
        $this->calculatePerformanceForTarget($target);
    }

    /**
     * Create conversion record from lead.
     */
    public function createConversionFromLead(Lead $lead, float $amount = null): ?SalesConversion
    {
        if (!$lead->user_id || $lead->status !== 1) { // Only for won leads
            return null;
        }

        $conversionAmount = $amount ?? $lead->lead_value ?? 0;
        
        if ($conversionAmount <= 0) {
            return null;
        }

        // Find applicable sales target
        $salesTarget = $this->findApplicableTarget($lead->user_id, $lead->closed_at ?? now());

        $conversion = SalesConversion::updateOrCreate([
            'lead_id' => $lead->id,
            'user_id' => $lead->user_id,
        ], [
            'sales_target_id' => $salesTarget?->id,
            'conversion_amount' => $conversionAmount,
            'conversion_date' => $lead->closed_at ?? now(),
            'conversion_type' => 'new_logo', // Default, can be customized
            'is_counted' => true,
        ]);

        // Update related target and performance
        if ($salesTarget) {
            $this->syncPerformanceWithTarget($salesTarget);
        }

        return $conversion;
    }

    /**
     * Find applicable sales target for a user and date.
     */
    protected function findApplicableTarget(int $userId, $date): ?SalesTarget
    {
        return SalesTarget::where('assignee_type', 'individual')
            ->where('assignee_id', $userId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('status', 'active')
            ->first();
    }
}
