<?php

namespace Webkul\Sales\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\Sales\Models\SalesPerformance;

class SalesPerformanceRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return SalesPerformance::class;
    }

    /**
     * Get performance summary.
     */
    public function getPerformanceSummary(string $period = 'monthly', string $dateFrom = null, string $dateTo = null): array
    {
        $query = $this->model->query();

        if ($dateFrom && $dateTo) {
            $query->where(function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('period_start', [$dateFrom, $dateTo])
                      ->orWhereBetween('period_end', [$dateFrom, $dateTo])
                      ->orWhere(function ($query) use ($dateFrom, $dateTo) {
                          $query->where('period_start', '<=', $dateFrom)
                                ->where('period_end', '>=', $dateTo);
                      });
            });
        } else if ($period) {
            $query->where('period_type', $period);
        }

        // Log the generated SQL query
        \Log::info('SalesPerformanceRepository getPerformanceSummary SQL: ' . $query->toSql());
        \Log::info('SalesPerformanceRepository getPerformanceSummary Bindings: ' . json_encode($query->getBindings()));

        $results = $query->get();

        \Log::info('SalesPerformanceRepository getPerformanceSummary Results: ' . json_encode($results));

        $totalRecords = $results->count();
        $totalTargetAmount = $results->sum('target_amount') ?? 0;
        $totalAchievedAmount = $results->sum('achieved_amount') ?? 0;
        $averageAchievement = $results->avg('achievement_percentage') ?? 0;
        $averageConversion = $results->avg('conversion_rate') ?? 0;

        return [
            'total_records'         => $totalRecords,
            'total_target_amount'   => $totalTargetAmount,
            'total_achieved_amount' => $totalAchievedAmount,
            'overall_achievement'   => $totalTargetAmount > 0 ?
                round(($totalAchievedAmount / $totalTargetAmount) * 100, 2) : 0,
            'average_achievement'   => round($averageAchievement, 2),
            'average_conversion'    => round($averageConversion, 2),
        ];
    }

    /**
     * Get leaderboard data.
     */
    public function getLeaderboard(string $type = 'individual', string $period = 'monthly', int $limit = 10, array $filters = []): Collection
    {
        $query = $this->model->query()
            ->where('entity_type', $type)
            ->where('period_type', $period);

        // Apply date filters
        if (isset($filters['date_from'])) {
            $query->where('period_start', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('period_end', '<=', $filters['date_to']);
        }

        // Get latest period for each entity
        $query->whereIn('id', function ($subQuery) use ($type, $period) {
            $subQuery->select('id')
                     ->from('sales_performance')
                     ->where('entity_type', $type)
                     ->where('period_type', $period)
                     ->whereRaw('period_start = (SELECT MAX(period_start) FROM sales_performance sp2 WHERE sp2.entity_id = sales_performance.entity_id AND sp2.entity_type = sales_performance.entity_type AND sp2.period_type = sales_performance.period_type)');
        });

        return $query->orderBy('score', 'desc')
                        ->orderBy('achievement_percentage', 'desc')
                        ->limit($limit)
                        ->get();
    }

    /**
     * Get target vs actual data.
     */
    public function getTargetVsActual(string $viewType = 'individual', string $period = 'monthly', string $dateFrom = null, string $dateTo = null): Collection
    {
        if ($viewType === 'total') {
            return $this->getTotalTargetVsActual($period, $dateFrom, $dateTo);
        }

        $query = $this->model->query()
            ->select([
                'entity_name',
                'entity_type',
                'entity_id',
                'target_amount',
                'achieved_amount',
                'achievement_percentage',
                'period_start',
                'period_end',
                'is_team_aggregate',
                'member_contributions',
                'rank',
                'score'
            ]);

        // Filter by view type
        if ($viewType === 'individual') {
            $query->where('entity_type', 'individual')
                  ->where('is_team_aggregate', false);
        } elseif ($viewType === 'team') {
            $query->where('entity_type', 'team')
                  ->where('is_team_aggregate', true);
        } elseif ($viewType === 'both') {
            // Show both individual and team performances
            $query->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->where('entity_type', 'individual')
                         ->where('is_team_aggregate', false);
                })->orWhere(function ($subQ) {
                    $subQ->where('entity_type', 'team')
                         ->where('is_team_aggregate', true);
                });
            });
        } else {
            // Default to individual
            $query->where('entity_type', 'individual')
                  ->where('is_team_aggregate', false);
        }

        if ($dateFrom && $dateTo) {
            $query->where(function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('period_start', [$dateFrom, $dateTo])
                      ->orWhereBetween('period_end', [$dateFrom, $dateTo])
                      ->orWhere(function ($query) use ($dateFrom, $dateTo) {
                          $query->where('period_start', '<=', $dateFrom)
                                ->where('period_end', '>=', $dateTo);
                      });
            });
        } else if ($period) {
            $query->where('period_type', $period);
        }

        \Log::info('SalesPerformanceRepository getTargetVsActual SQL: ' . $query->toSql());
        \Log::info('SalesPerformanceRepository getTargetVsActual Bindings: ' . json_encode($query->getBindings()));

        $results = $query->orderBy('period_start')
                        ->orderBy('achievement_percentage', 'desc')
                        ->get();

        \Log::info('SalesPerformanceRepository getTargetVsActual Results: ' . json_encode($results));

        if ($results->isEmpty()) {
            return $this->getSampleTargetVsActualData($viewType);
        }

        return $results;
    }

    /**
     * Get total target vs actual data.
     */
    public function getTotalTargetVsActual(string $period = 'monthly', string $dateFrom = null, string $dateTo = null): Collection
    {
        $query = $this->model->query()
            ->selectRaw('
                SUM(target_amount) as target_amount,
                SUM(achieved_amount) as achieved_amount
            ')
            ->where('period_type', $period);

        if ($dateFrom && $dateTo) {
            $query->where(function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('period_start', [$dateFrom, $dateTo])
                      ->orWhereBetween('period_end', [$dateFrom, $dateTo])
                      ->orWhere(function ($query) use ($dateFrom, $dateTo) {
                          $query->where('period_start', '<=', $dateFrom)
                                ->where('period_end', '>=', $dateTo);
                      });
            });
        } else if ($period) {
            $query->where('period_type', $period);
        }

        $result = $query->first();

        if (! $result || ! $result->target_amount) {
            return $this->getSampleTargetVsActualData();
        }

        return collect([
            [
                'entity_name'   => 'Total',
                'target_amount' => $result->target_amount,
                'achieved_amount' => $result->achieved_amount,
            ]
        ]);
    }

    /**
     * Get sample target vs actual data (fallback).
     */
    protected function getSampleTargetVsActualData(string $viewType = 'individual'): Collection
    {
        $data = collect();

        $entityPrefix = $viewType === 'team' ? 'Team' : 'User';

        for ($i = 0; $i < 5; $i++) {
            $target = rand(1000, 5000);
            $achieved = rand(0, $target);

            $data->push([
                'entity_name' => $entityPrefix . ' ' . $i,
                'entity_type' => $viewType === 'team' ? 'team' : 'individual',
                'target_amount' => $target,
                'achieved_amount' => $achieved,
                'achievement_percentage' => $target > 0 ? round(($achieved / $target) * 100, 2) : 0,
                'is_team_aggregate' => $viewType === 'team',
                'rank' => $i + 1,
                'score' => rand(60, 100),
            ]);
        }

        return $data;
    }

    /**
     * Get trends for a specific metric.
     */
    public function getTrends(string $metric = 'achievement_percentage', string $period = 'daily', string $dateFrom = null, string $dateTo = null, string $viewType = 'individual'): Collection
    {
        $query = $this->model->query()
            ->selectRaw("
                period_start,
                AVG({$metric}) as avg_value,
                MIN({$metric}) as min_value,
                MAX({$metric}) as max_value
            ");

        if ($viewType === 'individual') {
            $query->where('entity_type', 'individual');
        }

        if ($dateFrom && $dateTo) {
            $query->where(function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('period_start', [$dateFrom, $dateTo])
                      ->orWhereBetween('period_end', [$dateFrom, $dateTo])
                      ->orWhere(function ($query) use ($dateFrom, $dateTo) {
                          $query->where('period_start', '<=', $dateFrom)
                                ->where('period_end', '>=', $dateTo);
                      });
            });
        } else if ($period) {
            $query->where('period_type', $period);
        }

        $query->groupBy('period_start')
              ->orderBy('period_start');

        \Log::info('SalesPerformanceRepository getTrends SQL: ' . $query->toSql());
        \Log::info('SalesPerformanceRepository getTrends Bindings: ' . json_encode($query->getBindings()));

        $results = $query->get();

        \Log::info('SalesPerformanceRepository getTrends Results: ' . json_encode($results));

        if ($results->isEmpty()) {
            return $this->getSampleTrendData($dateFrom, $dateTo);
        }

        return $results;
    }

    /**
     * Get sample trend data (fallback).
     */
    protected function getSampleTrendData(string $dateFrom = null, string $dateTo = null): Collection
    {
        $startDate = $dateFrom ? \Carbon\Carbon::parse($dateFrom) : now()->subDays(30);
        $endDate = $dateTo ? \Carbon\Carbon::parse($dateTo) : now();

        $data = collect();
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $data->push([
                'period_start' => $current->format('Y-m-d'),
                'avg_value'    => rand(60, 95) + (rand(0, 100) / 100),
                'min_value'    => rand(30, 60),
                'max_value'    => rand(95, 100),
            ]);

            $current->addWeek();
        }

        return $data;
    }

    /**
     * Update performance rankings.
     */
    public function updateRankings(string $entityType = 'individual', string $period = 'monthly'): void
    {
        $query = $this->model->query()
            ->where('entity_type', $entityType)
            ->where('period_type', $period)
            ->whereRaw('period_start = (SELECT MAX(period_start) FROM sales_performance sp2 WHERE sp2.entity_type = ? AND sp2.period_type = ?)', [$entityType, $period]);

        // For teams, only rank aggregate performances
        if ($entityType === 'team') {
            $query->where('is_team_aggregate', true);
        } elseif ($entityType === 'individual') {
            $query->where('is_team_aggregate', false);
        }

        $performances = $query->orderBy('score', 'desc')
            ->orderBy('achievement_percentage', 'desc')
            ->get();

        $rank = 1;
        foreach ($performances as $performance) {
            $performance->update(['rank' => $rank]);
            $rank++;
        }
    }

    /**
     * Get team performance with member breakdown.
     */
    public function getTeamPerformanceWithMembers(int $teamId, string $period = 'monthly', string $dateFrom = null, string $dateTo = null): ?array
    {
        $teamPerformance = $this->model->query()
            ->where('entity_type', 'team')
            ->where('entity_id', $teamId)
            ->where('is_team_aggregate', true);

        if ($dateFrom && $dateTo) {
            $teamPerformance->whereBetween('period_start', [$dateFrom, $dateTo]);
        } else {
            $teamPerformance->where('period_type', $period);
        }

        $teamPerformance = $teamPerformance->latest('period_start')->first();

        if (!$teamPerformance) {
            return null;
        }

        // Get member performances
        $memberPerformances = $this->model->query()
            ->where('parent_performance_id', $teamPerformance->id)
            ->where('entity_type', 'individual')
            ->orderBy('achievement_percentage', 'desc')
            ->get();

        return [
            'team_performance' => $teamPerformance,
            'member_performances' => $memberPerformances,
            'member_contributions' => $teamPerformance->member_contributions ?? [],
        ];
    }

    /**
     * Get individual performance with team context.
     */
    public function getIndividualPerformanceWithTeamContext(int $userId, string $period = 'monthly', string $dateFrom = null, string $dateTo = null): array
    {
        $individualPerformance = $this->model->query()
            ->where('entity_type', 'individual')
            ->where('entity_id', $userId)
            ->where('is_team_aggregate', false);

        if ($dateFrom && $dateTo) {
            $individualPerformance->whereBetween('period_start', [$dateFrom, $dateTo]);
        } else {
            $individualPerformance->where('period_type', $period);
        }

        $individualPerformance = $individualPerformance->latest('period_start')->first();

        $result = [
            'individual_performance' => $individualPerformance,
            'team_performances' => [],
        ];

        if ($individualPerformance && $individualPerformance->parent_performance_id) {
            // Get team performance this individual contributes to
            $teamPerformance = $this->model->find($individualPerformance->parent_performance_id);
            if ($teamPerformance) {
                $result['team_performances'][] = $teamPerformance;
            }
        }

        return $result;
    }

    /**
     * Get performance comparison between individuals and teams.
     */
    public function getPerformanceComparison(string $period = 'monthly', string $dateFrom = null, string $dateTo = null): array
    {
        $individualQuery = $this->model->query()
            ->where('entity_type', 'individual')
            ->where('is_team_aggregate', false);

        $teamQuery = $this->model->query()
            ->where('entity_type', 'team')
            ->where('is_team_aggregate', true);

        if ($dateFrom && $dateTo) {
            $individualQuery->whereBetween('period_start', [$dateFrom, $dateTo]);
            $teamQuery->whereBetween('period_start', [$dateFrom, $dateTo]);
        } else {
            $individualQuery->where('period_type', $period);
            $teamQuery->where('period_type', $period);
        }

        $individualStats = [
            'total_target' => $individualQuery->sum('target_amount'),
            'total_achieved' => $individualQuery->sum('achieved_amount'),
            'avg_achievement' => $individualQuery->avg('achievement_percentage'),
            'top_performers' => $individualQuery->orderBy('achievement_percentage', 'desc')->limit(5)->get(),
        ];

        $teamStats = [
            'total_target' => $teamQuery->sum('target_amount'),
            'total_achieved' => $teamQuery->sum('achieved_amount'),
            'avg_achievement' => $teamQuery->avg('achievement_percentage'),
            'top_performers' => $teamQuery->orderBy('achievement_percentage', 'desc')->limit(5)->get(),
        ];

        return [
            'individual_stats' => $individualStats,
            'team_stats' => $teamStats,
            'comparison' => [
                'individual_vs_team_achievement' => $individualStats['avg_achievement'] - $teamStats['avg_achievement'],
                'total_performance_gap' => ($individualStats['total_achieved'] + $teamStats['total_achieved']) - ($individualStats['total_target'] + $teamStats['total_target']),
            ]
        ];
    }
}
