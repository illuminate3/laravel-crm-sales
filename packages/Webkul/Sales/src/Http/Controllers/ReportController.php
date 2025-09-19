<?php

namespace Webkul\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Webkul\Sales\DataGrids\SalesReportDataGrid;
use Webkul\Sales\Repositories\SalesReportRepository;

class ReportController extends Controller
{
    /**
     * Sales report repository instance.
     */
    protected $salesReportRepository;

    /**
     * Create a new controller instance.
     */
    public function __construct(SalesReportRepository $salesReportRepository)
    {
        $this->salesReportRepository = $salesReportRepository;
    }

    /**
     * Display a listing of the reports.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(SalesReportDataGrid::class)->toJson();
        }

        return view('sales::reports.index');
    }

    /**
     * Show the form for creating a new report.
     */
    public function create(): View
    {
        $reportTypes = $this->getReportTypes();
        $availableColumns = $this->getAvailableColumns();

        return view('sales::reports.create', compact('reportTypes', 'availableColumns'));
    }

    /**
     * Store a newly created report in storage.
     */
    public function store(): RedirectResponse
    {
        $this->validate(request(), [
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:commission,yoy_growth,pipeline_health,custom',
            'date_from'   => 'required|date',
            'date_to'     => 'required|date|after:date_from',
            'filters'     => 'nullable|array',
            'columns'     => 'required|array',
            'grouping'    => 'nullable|array',
            'sorting'     => 'nullable|array',
        ]);

        $data = request()->all();
        $data['created_by'] = auth()->id();
        $data['status'] = 'pending';

        $report = $this->salesReportRepository->create($data);

        // Queue report generation
        $this->salesReportRepository->generateReport($report->id);

        session()->flash('success', trans('sales::app.reports.create-success'));

        return redirect()->route('admin.sales.reports.index');
    }

    /**
     * Display the specified report.
     */
    public function view(int $id): View
    {
        $report = $this->salesReportRepository->findOrFail($id);

        return view('sales::reports.view', compact('report'));
    }

    /**
     * Export the specified report.
     */
    public function export(int $id): Response
    {
        $report = $this->salesReportRepository->findOrFail($id);

        if ($report->status !== 'completed') {
            abort(400, 'Report is not ready for export');
        }

        return $this->salesReportRepository->exportReport($report);
    }

    /**
     * Remove the specified report from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->salesReportRepository->delete($id);

        return new JsonResponse([
            'message' => trans('admin::app.layouts.delete-success'),
        ]);
    }

    /**
     * Mass delete reports.
     */
    public function massDestroy(): JsonResponse
    {
        $data = request()->validate([
            'indices' => 'required|array',
        ]);

        foreach ($data['indices'] as $id) {
            $this->salesReportRepository->delete($id);
        }

        return new JsonResponse([
            'message' => trans('admin::app.datagrid.mass-ops.delete-success'),
        ]);
    }

    /**
     * Get available report types.
     */
    protected function getReportTypes(): array
    {
        return [
            'commission' => [
                'name' => trans('sales::app.reports.commission'),
                'description' => 'Generate commission reports for sales representatives',
                'template' => 'commission',
            ],
            'yoy_growth' => [
                'name' => trans('sales::app.reports.yoy-growth'),
                'description' => 'Year-over-year growth analysis',
                'template' => 'yoy_growth',
            ],
            'pipeline_health' => [
                'name' => trans('sales::app.reports.pipeline-health'),
                'description' => 'Pipeline health and conversion analysis',
                'template' => 'pipeline_health',
            ],
            'custom' => [
                'name' => trans('sales::app.reports.custom'),
                'description' => 'Build your own custom report',
                'template' => 'custom',
            ],
        ];
    }

    /**
     * Get available columns for reports.
     */
    protected function getAvailableColumns(): array
    {
        return [
            'targets' => [
                'name' => 'Target Name',
                'target_amount' => 'Target Amount',
                'achieved_amount' => 'Achieved Amount',
                'progress_percentage' => 'Progress %',
                'assignee_name' => 'Assignee',
                'assignee_type' => 'Assignee Type',
                'start_date' => 'Start Date',
                'end_date' => 'End Date',
                'status' => 'Status',
            ],
            'performance' => [
                'entity_name' => 'Entity Name',
                'entity_type' => 'Entity Type',
                'target_amount' => 'Target Amount',
                'achieved_amount' => 'Achieved Amount',
                'achievement_percentage' => 'Achievement %',
                'leads_count' => 'Total Leads',
                'won_leads_count' => 'Won Leads',
                'lost_leads_count' => 'Lost Leads',
                'conversion_rate' => 'Conversion Rate',
                'average_deal_size' => 'Average Deal Size',
                'score' => 'Performance Score',
                'rank' => 'Rank',
            ],
            'leads' => [
                'title' => 'Lead Title',
                'lead_value' => 'Lead Value',
                'status' => 'Status',
                'source' => 'Source',
                'type' => 'Type',
                'person_name' => 'Contact Person',
                'organization_name' => 'Organization',
                'created_at' => 'Created Date',
                'expected_close_date' => 'Expected Close Date',
            ],
        ];
    }
}
