<?php

namespace Webkul\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Sales\Repositories\SalesPerformanceRepository;

class PerformanceController extends Controller
{
    /**
     * Sales performance repository instance.
     */
    protected $salesPerformanceRepository;

    /**
     * Create a new controller instance.
     */
    public function __construct(SalesPerformanceRepository $salesPerformanceRepository)
    {
        $this->salesPerformanceRepository = $salesPerformanceRepository;
    }

    /**
     * Display the performance dashboard.
     */
    public function index(): View
    {
        $viewType = request('view_type', 'individual'); // individual, team, or both
        $period = request('period', 'monthly');
        $dateFrom = request('date_from', now()->subMonths(3)->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));

        // Get initial data for the view
        $performanceData = $this->getPerformanceDataForView($viewType, $period, $dateFrom, $dateTo);

        return view('sales::performance.index', compact('performanceData', 'viewType', 'period'));
    }

    /**
     * Get performance statistics.
     */
    public function stats(): JsonResponse
    {
        $type = request('type', 'overview');

        switch ($type) {
            case 'overview':
                return $this->getOverviewStats();
            case 'target-vs-actual':
                return $this->getTargetVsActualStats();
            case 'conversion-rates':
                return $this->getConversionRateStats();
            case 'trends':
                return $this->getTrendStats();
            case 'team-breakdown':
                return $this->getTeamBreakdownStats();
            case 'individual-context':
                return $this->getIndividualContextStats();
            case 'comparison':
                return $this->getComparisonStats();
            default:
                return new JsonResponse(['error' => 'Invalid stats type'], 400);
        }
    }

    /**
     * View individual performance details.
     */
    public function view(int $id): View
    {
        $performance = $this->salesPerformanceRepository->findOrFail($id);

        return view('sales::performance.view', compact('performance'));
    }

    /**
     * Get leaderboard data.
     */
    public function leaderboard(): View|JsonResponse
    {
        $type = request('type', 'individual');
        $period = request('period', 'monthly');
        $limit = request('limit', 20);
        $filters = request('filters', []);

        $leaderboard = $this->salesPerformanceRepository->getLeaderboard($type, $period, $limit, $filters);

        if (request()->ajax()) {
            return new JsonResponse([
                'leaderboard' => $leaderboard,
            ]);
        }

        return view('sales::performance.leaderboard');
    }

    /**
     * Get overview statistics.
     */
    protected function getOverviewStats(): JsonResponse
    {
        $period = request('period', 'monthly');
        $dateFrom = request('date_from', now()->subMonths(3)->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));

        $summary = $this->salesPerformanceRepository->getPerformanceSummary($period, $dateFrom, $dateTo);

        return new JsonResponse($summary);
    }

    /**
     * Get target vs actual statistics.
     */
    protected function getTargetVsActualStats(): JsonResponse
    {
        $period = request('period', 'monthly');
        $dateFrom = request('date_from', now()->subMonths(6)->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));
        $viewType = request('view_type', 'individual');

        $data = $this->salesPerformanceRepository->getTargetVsActual($viewType, $period, $dateFrom, $dateTo);

        return new JsonResponse([
            'chart_data' => $data,
        ]);
    }

    /**
     * Get conversion rate statistics.
     */
    protected function getConversionRateStats(): JsonResponse
    {
        $period = request('period', 'monthly');
        $dateFrom = request('date_from', now()->subMonths(6)->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));

        $conversionRates = $this->salesPerformanceRepository->getConversionRates($period, $dateFrom, $dateTo);

        return new JsonResponse([
            'conversion_rates' => $conversionRates,
        ]);
    }

    /**
     * Get trend statistics.
     */
    protected function getTrendStats(): JsonResponse
    {
        $period = request('period', 'monthly');
        $dateFrom = request('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));
        $metric = request('metric', 'achievement_percentage');
        $viewType = request('view_type', 'individual');

        $trends = $this->salesPerformanceRepository->getTrends($metric, $period, $dateFrom, $dateTo, $viewType);

        return new JsonResponse([
            'trends' => $trends,
        ]);
    }

    /**
     * Get team breakdown statistics.
     */
    protected function getTeamBreakdownStats(): JsonResponse
    {
        $teamId = request('team_id');
        $period = request('period', 'monthly');
        $dateFrom = request('date_from', now()->subMonths(3)->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));

        if (!$teamId) {
            return new JsonResponse(['error' => 'Team ID is required'], 400);
        }

        $teamData = $this->salesPerformanceRepository->getTeamPerformanceWithMembers(
            $teamId, $period, $dateFrom, $dateTo
        );

        return new JsonResponse($teamData);
    }

    /**
     * Get individual performance with team context.
     */
    protected function getIndividualContextStats(): JsonResponse
    {
        $userId = request('user_id');
        $period = request('period', 'monthly');
        $dateFrom = request('date_from', now()->subMonths(3)->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));

        if (!$userId) {
            return new JsonResponse(['error' => 'User ID is required'], 400);
        }

        $individualData = $this->salesPerformanceRepository->getIndividualPerformanceWithTeamContext(
            $userId, $period, $dateFrom, $dateTo
        );

        return new JsonResponse($individualData);
    }

    /**
     * Get comparison statistics between individuals and teams.
     */
    protected function getComparisonStats(): JsonResponse
    {
        $period = request('period', 'monthly');
        $dateFrom = request('date_from', now()->subMonths(3)->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));

        $comparison = $this->salesPerformanceRepository->getPerformanceComparison(
            $period, $dateFrom, $dateTo
        );

        return new JsonResponse($comparison);
    }

    /**
     * Get performance data for the specified view type.
     */
    protected function getPerformanceDataForView(string $viewType, string $period, string $dateFrom, string $dateTo): array
    {
        $data = [
            'summary' => $this->salesPerformanceRepository->getPerformanceSummary($period, $dateFrom, $dateTo),
            'target_vs_actual' => $this->salesPerformanceRepository->getTargetVsActual($viewType, $period, $dateFrom, $dateTo),
            'leaderboard' => $this->salesPerformanceRepository->getLeaderboard($viewType, $period, 10),
        ];

        if ($viewType === 'both') {
            $data['comparison'] = $this->salesPerformanceRepository->getPerformanceComparison($period, $dateFrom, $dateTo);
        }

        return $data;
    }

    /**
     * Switch performance view type.
     */
    public function switchView(): JsonResponse
    {
        $viewType = request('view_type', 'individual');
        $period = request('period', 'monthly');
        $dateFrom = request('date_from', now()->subMonths(3)->format('Y-m-d'));
        $dateTo = request('date_to', now()->format('Y-m-d'));

        $performanceData = $this->getPerformanceDataForView($viewType, $period, $dateFrom, $dateTo);

        return new JsonResponse([
            'success' => true,
            'data' => $performanceData,
            'view_type' => $viewType,
        ]);
    }
}
