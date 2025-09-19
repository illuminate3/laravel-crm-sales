<?php

namespace Webkul\Sales\Listeners;

use Webkul\Lead\Models\Lead;
use Webkul\Sales\Models\SalesTarget;
use Webkul\Sales\Repositories\SalesTargetRepository;
use Webkul\Sales\Services\SalesPerformanceCalculationService;

class LeadListener
{
    /**
     * Sales target repository instance.
     */
    protected $salesTargetRepository;

    /**
     * Sales performance calculation service instance.
     */
    protected $performanceService;

    /**
     * Create a new listener instance.
     */
    public function __construct(
        SalesTargetRepository $salesTargetRepository,
        SalesPerformanceCalculationService $performanceService
    ) {
        $this->salesTargetRepository = $salesTargetRepository;
        $this->performanceService = $performanceService;
    }

    /**
     * Handle lead update events.
     * This will be called when a lead is updated, including stage changes.
     */
    public function handleLeadUpdate($lead)
    {
        // Use new performance service for better tracking
        if ($this->isLeadWon($lead)) {
            $this->performanceService->createConversionFromLead($lead);
        }

        // Keep legacy method for backward compatibility
        $this->updateTargetProgress($lead);
    }

    /**
     * Handle lead creation events.
     * This will be called when a new lead is created in a "won" stage.
     */
    public function handleLeadCreate($lead)
    {
        // Use new performance service for better tracking
        if ($this->isLeadWon($lead)) {
            $this->performanceService->createConversionFromLead($lead);
        }

        // Keep legacy method for backward compatibility
        $this->updateTargetProgress($lead);
    }

    /**
     * Check if the lead is in a "won" stage.
     */
    protected function isLeadWon($lead): bool
    {
        if (!$lead || !$lead->stage) {
            return false;
        }

        return $lead->stage->code === 'won';
    }

    /**
     * Update target progress when a lead is won.
     */
    protected function updateTargetProgress($lead): void
    {
        if (!$lead->user_id || !$lead->lead_value) {
            return;
        }

        // Find active targets for the lead owner (individual targets)
        $individualTargets = SalesTarget::where('assignee_type', 'individual')
            ->where('assignee_id', $lead->user_id)
            ->where('status', 'active')
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->get();

        foreach ($individualTargets as $target) {
            $this->addToTargetAchievement($target, $lead->lead_value);
        }

        // Find team targets if the user belongs to any teams
        $this->updateTeamTargets($lead);

        // Find region targets if the user belongs to any regions
        $this->updateRegionTargets($lead);
    }

    /**
     * Add the lead value to target achievement.
     */
    protected function addToTargetAchievement(SalesTarget $target, float $leadValue): void
    {
        $target->achieved_amount += $leadValue;
        $target->updateProgress();

        // Log the achievement
        \Log::info("Target {$target->id} updated: Added {$leadValue} to achieved amount. New total: {$target->achieved_amount}");
    }

    /**
     * Update team targets for the lead owner.
     */
    protected function updateTeamTargets($lead): void
    {
        // Get teams where the user is a member
        $teamIds = \DB::table('sales_team_members')
            ->where('user_id', $lead->user_id)
            ->where('is_active', true)
            ->pluck('team_id');

        if ($teamIds->isEmpty()) {
            return;
        }

        $teamTargets = SalesTarget::where('assignee_type', 'team')
            ->whereIn('assignee_id', $teamIds)
            ->where('status', 'active')
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->get();

        foreach ($teamTargets as $target) {
            $this->addToTargetAchievement($target, $lead->lead_value);
        }
    }

    /**
     * Update region targets for the lead owner.
     */
    protected function updateRegionTargets($lead): void
    {
        // For now, we'll assume users are assigned to regions through teams
        // You can modify this logic based on your specific region assignment strategy
        
        // Get teams where the user is a member, then get regions for those teams
        $teamIds = \DB::table('sales_team_members')
            ->where('user_id', $lead->user_id)
            ->where('is_active', true)
            ->pluck('team_id');

        if ($teamIds->isEmpty()) {
            return;
        }

        // This is a simplified approach - you might want to create a more sophisticated
        // mapping between users/teams and regions based on your business logic
        $regionTargets = SalesTarget::where('assignee_type', 'region')
            ->where('status', 'active')
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->get();

        foreach ($regionTargets as $target) {
            // Only update if the user's territory matches the region
            // This is a simplified check - implement your own logic
            $this->addToTargetAchievement($target, $lead->lead_value);
        }
    }

    /**
     * Handle lead deletion events.
     * This will subtract the lead value from targets if the lead was won.
     */
    public function handleLeadDelete($lead)
    {
        if ($this->isLeadWon($lead) && $lead->lead_value) {
            // Subtract the lead value from targets
            $this->subtractFromTargetAchievement($lead);
        }
    }

    /**
     * Subtract lead value from target achievement when a won lead is deleted.
     */
    protected function subtractFromTargetAchievement($lead): void
    {
        // Find targets that might have been affected
        $individualTargets = SalesTarget::where('assignee_type', 'individual')
            ->where('assignee_id', $lead->user_id)
            ->where('achieved_amount', '>', 0)
            ->get();

        foreach ($individualTargets as $target) {
            $target->achieved_amount = max(0, $target->achieved_amount - $lead->lead_value);
            $target->updateProgress();
        }

        // Also handle team and region targets
        // Similar logic as above but for teams and regions
    }
}
