<?php

namespace Webkul\Sales\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Sales\Models\SalesTarget;
use Webkul\Sales\Models\SalesPerformance;
use Webkul\Sales\Models\SalesConversion;
use Webkul\Sales\Models\SalesTeamMember;

class ValidateSalesDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:validate-data 
                            {--fix : Automatically fix issues where possible}
                            {--detailed : Show detailed information about issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate sales data integrity and consistency';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Validating sales data integrity...');
        
        $issues = [];
        $fixable = [];

        // Run all validation checks
        $issues = array_merge($issues, $this->validateTargetConsistency());
        $issues = array_merge($issues, $this->validatePerformanceConsistency());
        $issues = array_merge($issues, $this->validateTeamHierarchy());
        $issues = array_merge($issues, $this->validateConversions());
        $issues = array_merge($issues, $this->validateRelationships());

        // Display results
        if (empty($issues)) {
            $this->info('✅ All sales data validation checks passed!');
            return 0;
        }

        $this->error("❌ Found " . count($issues) . " data integrity issues:");
        
        foreach ($issues as $issue) {
            $this->line("  • " . $issue['message']);
            
            if ($this->option('detailed') && isset($issue['details'])) {
                foreach ($issue['details'] as $detail) {
                    $this->line("    - " . $detail);
                }
            }

            if (isset($issue['fixable']) && $issue['fixable']) {
                $fixable[] = $issue;
            }
        }

        // Offer to fix issues
        if (!empty($fixable) && $this->option('fix')) {
            $this->info("\nAttempting to fix " . count($fixable) . " fixable issues...");
            $this->fixIssues($fixable);
        } elseif (!empty($fixable)) {
            $this->info("\n" . count($fixable) . " issues can be automatically fixed. Run with --fix to fix them.");
        }

        return count($issues);
    }

    /**
     * Validate target consistency.
     */
    protected function validateTargetConsistency(): array
    {
        $issues = [];

        // Check for targets with negative achieved amounts
        $negativeAchieved = SalesTarget::where('achieved_amount', '<', 0)->get();
        if ($negativeAchieved->count() > 0) {
            $issues[] = [
                'message' => "Found {$negativeAchieved->count()} targets with negative achieved amounts",
                'details' => $negativeAchieved->pluck('name')->toArray(),
                'fixable' => true,
                'fix_method' => 'fixNegativeAchievedAmounts'
            ];
        }

        // Check for targets with achieved > target * 2 (suspicious)
        $suspiciousTargets = SalesTarget::whereRaw('achieved_amount > target_amount * 2')->get();
        if ($suspiciousTargets->count() > 0) {
            $issues[] = [
                'message' => "Found {$suspiciousTargets->count()} targets with suspiciously high achievement (>200%)",
                'details' => $suspiciousTargets->map(fn($t) => "{$t->name}: {$t->achieved_amount}/{$t->target_amount}")->toArray(),
                'fixable' => false
            ];
        }

        // Check for targets with invalid date ranges
        $invalidDates = SalesTarget::whereRaw('start_date > end_date')->get();
        if ($invalidDates->count() > 0) {
            $issues[] = [
                'message' => "Found {$invalidDates->count()} targets with invalid date ranges (start > end)",
                'details' => $invalidDates->pluck('name')->toArray(),
                'fixable' => false
            ];
        }

        return $issues;
    }

    /**
     * Validate performance consistency.
     */
    protected function validatePerformanceConsistency(): array
    {
        $issues = [];

        // Check for duplicate performance records
        $duplicates = DB::table('sales_performance')
            ->select(['entity_type', 'entity_id', 'period_start', 'period_end', 'period_type'])
            ->groupBy(['entity_type', 'entity_id', 'period_start', 'period_end', 'period_type'])
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->count() > 0) {
            $issues[] = [
                'message' => "Found {$duplicates->count()} duplicate performance record groups",
                'fixable' => true,
                'fix_method' => 'fixDuplicatePerformances'
            ];
        }

        // Check for performance records with null calculated_at
        $uncalculated = SalesPerformance::whereNull('calculated_at')->count();
        if ($uncalculated > 0) {
            $issues[] = [
                'message' => "Found {$uncalculated} performance records that haven't been calculated",
                'fixable' => true,
                'fix_method' => 'fixUncalculatedPerformances'
            ];
        }

        // Check for team performances without member contributions
        $teamsWithoutMembers = SalesPerformance::where('entity_type', 'team')
            ->where('is_team_aggregate', true)
            ->where(function($q) {
                $q->whereNull('member_contributions')
                  ->orWhereRaw('JSON_LENGTH(member_contributions) = 0');
            })
            ->count();

        if ($teamsWithoutMembers > 0) {
            $issues[] = [
                'message' => "Found {$teamsWithoutMembers} team performances without member contributions",
                'fixable' => true,
                'fix_method' => 'fixTeamMemberContributions'
            ];
        }

        return $issues;
    }

    /**
     * Validate team hierarchy.
     */
    protected function validateTeamHierarchy(): array
    {
        $issues = [];

        // Check for team members without role names
        $membersWithoutRoles = SalesTeamMember::where('is_active', true)
            ->where(function($q) {
                $q->whereNull('role_name')->orWhere('role_name', '');
            })
            ->count();

        if ($membersWithoutRoles > 0) {
            $issues[] = [
                'message' => "Found {$membersWithoutRoles} active team members without role names",
                'fixable' => true,
                'fix_method' => 'fixTeamMemberRoles'
            ];
        }

        // Check for orphaned individual performances (no parent team)
        $orphanedIndividuals = SalesPerformance::where('entity_type', 'individual')
            ->whereNotNull('parent_performance_id')
            ->whereDoesntHave('parentPerformance')
            ->count();

        if ($orphanedIndividuals > 0) {
            $issues[] = [
                'message' => "Found {$orphanedIndividuals} individual performances with invalid parent references",
                'fixable' => true,
                'fix_method' => 'fixOrphanedIndividualPerformances'
            ];
        }

        return $issues;
    }

    /**
     * Validate conversions.
     */
    protected function validateConversions(): array
    {
        $issues = [];

        // Check for conversions with zero or negative amounts
        $invalidAmounts = SalesConversion::where('conversion_amount', '<=', 0)->count();
        if ($invalidAmounts > 0) {
            $issues[] = [
                'message' => "Found {$invalidAmounts} conversions with zero or negative amounts",
                'fixable' => true,
                'fix_method' => 'fixInvalidConversionAmounts'
            ];
        }

        // Check for conversions without valid leads
        $orphanedConversions = SalesConversion::whereDoesntHave('lead')->count();
        if ($orphanedConversions > 0) {
            $issues[] = [
                'message' => "Found {$orphanedConversions} conversions without valid leads",
                'fixable' => true,
                'fix_method' => 'fixOrphanedConversions'
            ];
        }

        return $issues;
    }

    /**
     * Validate relationships.
     */
    protected function validateRelationships(): array
    {
        $issues = [];

        // Check for performance records with invalid target references
        $invalidTargetRefs = SalesPerformance::whereNotNull('sales_target_id')
            ->whereDoesntHave('salesTarget')
            ->count();

        if ($invalidTargetRefs > 0) {
            $issues[] = [
                'message' => "Found {$invalidTargetRefs} performance records with invalid target references",
                'fixable' => true,
                'fix_method' => 'fixInvalidTargetReferences'
            ];
        }

        return $issues;
    }

    /**
     * Fix identified issues.
     */
    protected function fixIssues(array $fixableIssues): void
    {
        foreach ($fixableIssues as $issue) {
            if (isset($issue['fix_method'])) {
                $method = $issue['fix_method'];
                if (method_exists($this, $method)) {
                    $this->info("Fixing: " . $issue['message']);
                    $this->$method();
                }
            }
        }
    }

    /**
     * Fix negative achieved amounts.
     */
    protected function fixNegativeAchievedAmounts(): void
    {
        SalesTarget::where('achieved_amount', '<', 0)->update(['achieved_amount' => 0]);
        $this->info("  ✅ Reset negative achieved amounts to zero");
    }

    /**
     * Fix duplicate performance records.
     */
    protected function fixDuplicatePerformances(): void
    {
        $duplicates = DB::table('sales_performance')
            ->select([
                'entity_type', 'entity_id', 'period_start', 'period_end', 'period_type',
                DB::raw('MIN(id) as keep_id'),
                DB::raw('GROUP_CONCAT(id) as all_ids')
            ])
            ->groupBy(['entity_type', 'entity_id', 'period_start', 'period_end', 'period_type'])
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $deleted = 0;
        foreach ($duplicates as $duplicate) {
            $allIds = explode(',', $duplicate->all_ids);
            $idsToDelete = array_filter($allIds, fn($id) => $id != $duplicate->keep_id);
            
            if (!empty($idsToDelete)) {
                SalesPerformance::whereIn('id', $idsToDelete)->delete();
                $deleted += count($idsToDelete);
            }
        }

        $this->info("  ✅ Removed {$deleted} duplicate performance records");
    }

    /**
     * Fix uncalculated performances.
     */
    protected function fixUncalculatedPerformances(): void
    {
        SalesPerformance::whereNull('calculated_at')->update(['calculated_at' => now()]);
        $this->info("  ✅ Updated calculated_at timestamps");
    }

    /**
     * Fix team member roles.
     */
    protected function fixTeamMemberRoles(): void
    {
        $members = SalesTeamMember::where('is_active', true)
            ->where(function($q) {
                $q->whereNull('role_name')->orWhere('role_name', '');
            })
            ->with(['user.role'])
            ->get();

        $updated = 0;
        foreach ($members as $member) {
            if ($member->user && $member->user->role) {
                $member->update(['role_name' => $member->user->role->name]);
                $updated++;
            }
        }

        $this->info("  ✅ Updated role names for {$updated} team members");
    }

    /**
     * Fix invalid conversion amounts.
     */
    protected function fixInvalidConversionAmounts(): void
    {
        SalesConversion::where('conversion_amount', '<=', 0)->delete();
        $this->info("  ✅ Removed conversions with invalid amounts");
    }

    /**
     * Fix orphaned conversions.
     */
    protected function fixOrphanedConversions(): void
    {
        SalesConversion::whereDoesntHave('lead')->delete();
        $this->info("  ✅ Removed orphaned conversions");
    }

    /**
     * Fix invalid target references.
     */
    protected function fixInvalidTargetReferences(): void
    {
        SalesPerformance::whereNotNull('sales_target_id')
            ->whereDoesntHave('salesTarget')
            ->update(['sales_target_id' => null]);
        $this->info("  ✅ Cleared invalid target references");
    }

    /**
     * Fix team performances without member contributions.
     */
    protected function fixTeamMemberContributions(): void
    {
        $teamsWithoutMembers = SalesPerformance::where('entity_type', 'team')
            ->where('is_team_aggregate', true)
            ->where(function($q) {
                $q->whereNull('member_contributions')
                  ->orWhereRaw('JSON_LENGTH(member_contributions) = 0');
            })
            ->get();

        $updated = 0;
        foreach ($teamsWithoutMembers as $teamPerformance) {
            $this->info("  Processing team performance ID: {$teamPerformance->id}, Entity ID: {$teamPerformance->entity_id}");
            $memberContributions = [];

            // Find team members associated with this team
            $teamMembers = SalesTeamMember::where('team_id', $teamPerformance->entity_id)
                                        ->where('is_active', true)
                                        ->get();

            if ($teamMembers->isEmpty()) {
                $this->warn("    No active team members found for team ID: {$teamPerformance->entity_id}");
                continue;
            }

            foreach ($teamMembers as $member) {
                $this->info("    Checking member: {$member->user_id}");
                // Find individual performance for this member within the same period
                $individualPerformance = SalesPerformance::where('entity_type', 'individual')
                    ->where('entity_id', $member->user_id)
                    ->where('period_start', $teamPerformance->period_start)
                    ->where('period_end', $teamPerformance->period_end)
                    ->where('period_type', $teamPerformance->period_type)
                    ->first();

                if ($individualPerformance) {
                    $this->info("      Found individual performance for user {$member->user_id}: ID {$individualPerformance->id}, Achieved: {$individualPerformance->achieved_amount}");
                    $memberContributions[] = [
                        'user_id' => $member->user_id,
                        'name' => $member->user->name ?? 'Unknown',
                        'achieved_amount' => $individualPerformance->achieved_amount,
                        'contribution_percentage' => $member->contribution_percentage,
                    ];
                } else {
                    $this->warn("      No individual performance found for user {$member->user_id} in period {$teamPerformance->period_start} to {$teamPerformance->period_end} ({$teamPerformance->period_type})");
                }
            }

            if (!empty($memberContributions)) {
                $teamPerformance->update(['member_contributions' => $memberContributions]);
                $updated++;
            } else {
                $this->warn("    No member contributions generated for team performance ID: {$teamPerformance->id}");
            }
        }

        $this->info("  ✅ Updated member contributions for {$updated} team performances");
    }
}
