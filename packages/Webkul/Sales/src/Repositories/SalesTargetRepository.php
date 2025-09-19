<?php

namespace Webkul\Sales\Repositories;

use Illuminate\Http\JsonResponse;
use Webkul\Core\Eloquent\Repository;
use Webkul\Sales\Models\SalesTarget;

class SalesTargetRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return SalesTarget::class;
    }

    /**
     * Get data grid data for targets.
     */
    public function getDataGridData(): JsonResponse
    {
        $query = $this->model->query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->select([
                'id',
                'name',
                'target_amount',
                'achieved_amount',
                'progress_percentage',
                'assignee_type',
                'assignee_name',
                'start_date',
                'end_date',
                'status',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at'
            ]);

        // Apply filters
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('assignee_name', 'like', "%{$search}%");
            });
        }

        if (request('assignee_type')) {
            $query->where('assignee_type', request('assignee_type'));
        }

        if (request('status')) {
            $query->where('status', request('status'));
        }

        if (request('date_from')) {
            $query->where('start_date', '>=', request('date_from'));
        }

        if (request('date_to')) {
            $query->where('end_date', '<=', request('date_to'));
        }

        // Apply sorting
        $sortBy = request('sort', 'created_at');
        $sortOrder = request('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = request('per_page', 15);
        $targets = $query->paginate($perPage);

        return new JsonResponse([
            'data' => $targets->items(),
            'meta' => [
                'current_page' => $targets->currentPage(),
                'last_page'    => $targets->lastPage(),
                'per_page'     => $targets->perPage(),
                'total'        => $targets->total(),
            ],
        ]);
    }

    /**
     * Get targets for a specific assignee.
     */
    public function getTargetsForAssignee(string $type, int $id, array $filters = [])
    {
        $query = $this->model->query()
            ->where('assignee_type', $type)
            ->where('assignee_id', $id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['period'])) {
            $query->where('period_type', $filters['period']);
        }

        if (isset($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('end_date', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    /**
     * Get active targets.
     */
    public function getActiveTargets()
    {
        return $this->model->active()->get();
    }

    /**
     * Get targets summary statistics.
     */
    public function getTargetsSummary(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', 'active')->count();
        $achieved = $this->model->where('progress_percentage', '>=', 100)->count();
        $totalTargetAmount = $this->model->sum('target_amount');
        $totalAchievedAmount = $this->model->sum('achieved_amount');

        return [
            'total_targets'         => $total,
            'active_targets'        => $active,
            'achieved_targets'      => $achieved,
            'total_target_amount'   => $totalTargetAmount,
            'total_achieved_amount' => $totalAchievedAmount,
            'overall_achievement'   => $totalTargetAmount > 0 ? 
                round(($totalAchievedAmount / $totalTargetAmount) * 100, 2) : 0,
        ];
    }

    /**
     * Update target progress based on actual sales data.
     */
    public function updateTargetProgress(int $targetId, float $achievedAmount): void
    {
        $target = $this->find($targetId);
        
        if ($target) {
            $target->achieved_amount = $achievedAmount;
            $target->updateProgress();
        }
    }

    /**
     * Get targets that need progress calculation.
     */
    public function getTargetsForProgressUpdate()
    {
        return $this->model->active()
            ->where(function ($query) {
                $query->whereNull('last_calculated_at')
                      ->orWhere('last_calculated_at', '<', now()->subHours(1));
            })
            ->get();
    }

    /**
     * Get targets over time for charts.
     */
    public function getTargetsOverTime(string $period = 'monthly', string $dateFrom = null, string $dateTo = null)
    {
        $query = $this->model->query()
            ->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as period,
                COUNT(*) as count,
                SUM(target_amount) as total_target,
                SUM(achieved_amount) as total_achieved
            ')
            ->groupBy('period')
            ->orderBy('period');

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        return $query->get();
    }

    /**
     * Get targets by assignee type.
     */
    public function getTargetsByAssigneeType()
    {
        return $this->model->query()
            ->selectRaw('assignee_type, COUNT(*) as count')
            ->groupBy('assignee_type')
            ->get();
    }

    /**
     * Get targets by status.
     */
    public function getTargetsByStatus()
    {
        return $this->model->query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
    }
}
