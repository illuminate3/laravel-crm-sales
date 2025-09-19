<?php

namespace Webkul\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedSalesTargets();
        $this->seedSalesPerformance();
        $this->seedSalesReports();
    }

    /**
     * Seed sales targets table.
     */
    protected function seedSalesTargets(): void
    {
        $users = DB::table('users')->select('id', 'name')->get();

        if ($users->isEmpty()) {
            // Create a default user if none exist
            $userId = DB::table('users')->insertGetId([
                'name' => 'Sales Manager',
                'email' => 'sales@example.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $users = collect([
                (object) ['id' => $userId, 'name' => 'Sales Manager']
            ]);
        }

        $targets = [];
        $currentDate = Carbon::now();

        foreach ($users as $user) {
            // Create quarterly targets
            for ($quarter = 0; $quarter < 4; $quarter++) {
                $startDate = $currentDate->copy()->startOfYear()->addQuarters($quarter);
                $endDate = $startDate->copy()->endOfQuarter();
                
                $targetAmount = rand(50000, 200000);
                $achievedAmount = rand(30000, $targetAmount);
                
                $targets[] = [
                    'name' => "Q" . ($quarter + 1) . " Target - " . $user->name,
                    'description' => "Quarterly sales target for " . $user->name,
                    'target_amount' => $targetAmount,
                    'achieved_amount' => $achievedAmount,
                    'assignee_type' => 'individual',
                    'assignee_id' => $user->id,
                    'assignee_name' => $user->name,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'period_type' => 'quarterly',
                    'status' => 'active',
                    'progress_percentage' => round(($achievedAmount / $targetAmount) * 100, 2),
                    'last_calculated_at' => now(),
                    'created_by' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            // Create monthly targets for current year
            for ($month = 1; $month <= 12; $month++) {
                $startDate = $currentDate->copy()->month($month)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
                
                $targetAmount = rand(15000, 50000);
                $achievedAmount = rand(10000, $targetAmount);
                
                $targets[] = [
                    'name' => $startDate->format('M Y') . " Target - " . $user->name,
                    'description' => "Monthly sales target for " . $user->name,
                    'target_amount' => $targetAmount,
                    'achieved_amount' => $achievedAmount,
                    'assignee_type' => 'individual',
                    'assignee_id' => $user->id,
                    'assignee_name' => $user->name,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'period_type' => 'monthly',
                    'status' => 'active',
                    'progress_percentage' => round(($achievedAmount / $targetAmount) * 100, 2),
                    'last_calculated_at' => now(),
                    'created_by' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('sales_targets')->insert($targets);
    }

    /**
     * Seed sales performance table.
     */
    protected function seedSalesPerformance(): void
    {
        $users = DB::table('users')->select('id', 'name')->get();
        $performance = [];
        $teamPerformance = [];
        $currentDate = Carbon::now();

        foreach ($users as $user) {
            // Create monthly performance records
            for ($month = 1; $month <= 12; $month++) {
                $startDate = $currentDate->copy()->month($month)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
                
                $targetAmount = rand(15000, 50000);
                $achievedAmount = rand(10000, $targetAmount);
                $achievementPercentage = round(($achievedAmount / $targetAmount) * 100, 2);
                
                $leadsCount = rand(50, 200);
                $wonLeads = rand(10, 50);
                $lostLeads = rand(5, 30);
                $conversionRate = round(($wonLeads / $leadsCount) * 100, 2);
                
                $performance[] = [
                    'entity_id' => $user->id,
                    'entity_name' => $user->name,
                    'entity_type' => 'individual',
                    'period_type' => 'monthly',
                    'period_start' => $startDate->format('Y-m-d'),
                    'period_end' => $endDate->format('Y-m-d'),
                    'target_amount' => $targetAmount,
                    'achieved_amount' => $achievedAmount,
                    'achievement_percentage' => $achievementPercentage,
                    'leads_count' => $leadsCount,
                    'won_leads_count' => $wonLeads,
                    'lost_leads_count' => $lostLeads,
                    'conversion_rate' => $conversionRate,
                    'average_deal_size' => round($achievedAmount / max($wonLeads, 1), 2),
                    'score' => round(($achievementPercentage * 0.6) + ($conversionRate * 0.4), 2),
                    'rank' => 1, // Will be updated later
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('sales_performance')->insert($performance);

        // Seed team performance records
        $teams = DB::table('sales_teams')->select('id', 'name')->get();

        foreach ($teams as $team) {
            for ($month = 1; $month <= 12; $month++) {
                $startDate = $currentDate->copy()->month($month)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();

                $targetAmount = rand(100000, 500000);
                $achievedAmount = rand(50000, $targetAmount);
                $achievementPercentage = round(($achievedAmount / $targetAmount) * 100, 2);

                $leadsCount = rand(100, 500);
                $wonLeads = rand(20, 100);
                $lostLeads = rand(10, 50);
                $conversionRate = round(($wonLeads / $leadsCount) * 100, 2);

                $teamPerformance[] = [
                    'entity_id' => $team->id,
                    'entity_name' => $team->name,
                    'entity_type' => 'team',
                    'period_type' => 'monthly',
                    'period_start' => $startDate->format('Y-m-d'),
                    'period_end' => $endDate->format('Y-m-d'),
                    'target_amount' => $targetAmount,
                    'achieved_amount' => $achievedAmount,
                    'achievement_percentage' => $achievementPercentage,
                    'leads_count' => $leadsCount,
                    'won_leads_count' => $wonLeads,
                    'lost_leads_count' => $lostLeads,
                    'conversion_rate' => $conversionRate,
                    'average_deal_size' => round($achievedAmount / max($wonLeads, 1), 2),
                    'score' => round(($achievementPercentage * 0.6) + ($conversionRate * 0.4), 2),
                    'rank' => 1, // Will be updated later
                    'is_team_aggregate' => true,
                    'member_contributions' => json_encode([]), // Placeholder
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($teamPerformance)) {
            DB::table('sales_performance')->insert($teamPerformance);
        }
        
        // Update rankings
        $this->updateRankings();
    }

    /**
     * Seed sales reports table.
     */
    protected function seedSalesReports(): void
    {
        $userId = DB::table('users')->value('id');
        
        $reports = [
            [
                'name' => 'Monthly Performance Report',
                'description' => 'Monthly sales performance analysis',
                'type' => 'commission',
                'status' => 'completed',
                'date_from' => now()->startOfMonth()->format('Y-m-d'),
                'date_to' => now()->endOfMonth()->format('Y-m-d'),
                'filters' => json_encode(['period' => 'monthly']),
                'columns' => json_encode(['entity_name', 'target_amount', 'achieved_amount', 'achievement_percentage']),
                'grouping' => json_encode(['entity_type']),
                'sorting' => json_encode(['achievement_percentage' => 'desc']),
                'data' => json_encode([]),
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Quarterly Growth Analysis',
                'description' => 'Year-over-year quarterly growth analysis',
                'type' => 'yoy_growth',
                'status' => 'completed',
                'date_from' => now()->startOfQuarter()->format('Y-m-d'),
                'date_to' => now()->endOfQuarter()->format('Y-m-d'),
                'filters' => json_encode(['period' => 'quarterly']),
                'columns' => json_encode(['entity_name', 'target_amount', 'achieved_amount', 'conversion_rate']),
                'grouping' => json_encode(['period_start']),
                'sorting' => json_encode(['period_start' => 'asc']),
                'data' => json_encode([]),
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('sales_reports')->insert($reports);
    }

    /**
     * Update performance rankings.
     */
    protected function updateRankings(): void
    {
        $performances = DB::table('sales_performance')
            ->where('entity_type', 'individual')
            ->where('period_type', 'monthly')
            ->orderBy('score', 'desc')
            ->orderBy('achievement_percentage', 'desc')
            ->get();

        $rank = 1;
        foreach ($performances as $performance) {
            DB::table('sales_performance')
                ->where('id', $performance->id)
                ->update(['rank' => $rank]);
            $rank++;
        }
    }
}
