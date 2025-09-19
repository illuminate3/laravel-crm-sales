<?php

namespace Webkul\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Sales\Repositories\SalesTargetRepository;

class DashboardController extends Controller
{
    /**
     * Sales target repository instance.
     */
    protected $salesTargetRepository;

    /**
     * Create a new controller instance.
     */
    public function __construct(SalesTargetRepository $salesTargetRepository)
    {
        $this->salesTargetRepository = $salesTargetRepository;
    }

    /**
     * Display the sales dashboard.
     */
    public function index(): View
    {
        return view('sales::dashboard.index');
    }

    /**
     * Returns the sales dashboard stats.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'targets'     => [
                'total_targets'       => 100,
                'active_targets'      => 80,
                'overall_achievement' => 75,
                'achieved_targets'    => 60,
                'total_target_amount' => 100000,
                'total_achieved_amount' => 75000,
            ],
            'performance' => [
                'targets_over_time'       => [
                    ['period' => 'Jan', 'count' => 10],
                    ['period' => 'Feb', 'count' => 15],
                    ['period' => 'Mar', 'count' => 20],
                ],
                'performance_distribution' => [
                    '100%+'  => 25,
                    '75-99%' => 35,
                    '50-74%' => 25,
                    '<50%'   => 15,
                ],
            ],
        ]);
    }
}
