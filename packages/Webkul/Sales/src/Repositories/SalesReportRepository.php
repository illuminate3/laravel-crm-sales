<?php

namespace Webkul\Sales\Repositories;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\Repository;
use Webkul\Sales\Models\SalesReport;

class SalesReportRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return SalesReport::class;
    }

    /**
     * Get data grid data for reports.
     */
    public function getDataGridData(): JsonResponse
    {
        $query = $this->model->query()
            ->with(['creator:id,name'])
            ->accessibleBy(auth()->id())
            ->select([
                'id',
                'name',
                'type',
                'status',
                'date_from',
                'date_to',
                'generated_at',
                'created_by',
                'is_public',
                'is_scheduled',
                'created_at',
                'updated_at'
            ]);

        // Apply filters
        if (request('search')) {
            $search = request('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if (request('type')) {
            $query->where('type', request('type'));
        }

        if (request('status')) {
            $query->where('status', request('status'));
        }

        if (request('date_from')) {
            $query->where('date_from', '>=', request('date_from'));
        }

        if (request('date_to')) {
            $query->where('date_to', '<=', request('date_to'));
        }

        // Apply sorting
        $sortBy = request('sort', 'created_at');
        $sortOrder = request('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = request('per_page', 15);
        $reports = $query->paginate($perPage);

        return new JsonResponse([
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page'    => $reports->lastPage(),
                'per_page'     => $reports->perPage(),
                'total'        => $reports->total(),
            ],
        ]);
    }

    /**
     * Generate report data.
     */
    public function generateReport(int $reportId): void
    {
        $report = $this->find($reportId);

        if (!$report) {
            return;
        }

        try {
            $report->update(['status' => 'processing']);

            $data = $this->buildReportData($report);

            $report->update([
                'status' => 'completed',
                'data' => json_encode($data),
                'generated_at' => now(),
                'error_message' => null,
            ]);

            // Generate file if needed
            $this->generateReportFile($report, $data);

        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build report data based on type and filters.
     */
    protected function buildReportData(SalesReport $report): array
    {
        switch ($report->type) {
            case 'commission':
                return $this->buildCommissionReport($report);
            case 'yoy_growth':
                return $this->buildYoyGrowthReport($report);
            case 'pipeline_health':
                return $this->buildPipelineHealthReport($report);
            case 'custom':
                return $this->buildCustomReport($report);
            default:
                throw new \Exception('Unknown report type: ' . $report->type);
        }
    }

    /**
     * Build commission report data.
     */
    protected function buildCommissionReport(SalesReport $report): array
    {
        // Implementation for commission report
        // This would query sales data and calculate commissions
        return [
            'headers' => ['Sales Rep', 'Total Sales', 'Commission Rate', 'Commission Amount'],
            'rows' => [
                // Sample data - replace with actual query results
                ['John Doe', '$50,000', '5%', '$2,500'],
                ['Jane Smith', '$75,000', '6%', '$4,500'],
            ],
            'summary' => [
                'total_sales' => 125000,
                'total_commission' => 7000,
            ],
        ];
    }

    /**
     * Build year-over-year growth report data.
     */
    protected function buildYoyGrowthReport(SalesReport $report): array
    {
        // Implementation for YoY growth report
        return [
            'headers' => ['Period', 'Current Year', 'Previous Year', 'Growth %'],
            'rows' => [
                // Sample data - replace with actual query results
                ['Q1', '$100,000', '$80,000', '25%'],
                ['Q2', '$120,000', '$90,000', '33%'],
            ],
            'summary' => [
                'total_current' => 220000,
                'total_previous' => 170000,
                'overall_growth' => 29.4,
            ],
        ];
    }

    /**
     * Build pipeline health report data.
     */
    protected function buildPipelineHealthReport(SalesReport $report): array
    {
        // Implementation for pipeline health report
        return [
            'headers' => ['Stage', 'Count', 'Value', 'Avg. Days', 'Conversion Rate'],
            'rows' => [
                // Sample data - replace with actual query results
                ['Prospecting', '50', '$500,000', '15', '60%'],
                ['Qualification', '30', '$300,000', '20', '70%'],
            ],
            'summary' => [
                'total_pipeline_value' => 800000,
                'total_leads' => 80,
                'avg_conversion_rate' => 65,
            ],
        ];
    }

    /**
     * Build custom report data.
     */
    protected function buildCustomReport(SalesReport $report): array
    {
        // Implementation for custom report based on selected columns and filters
        $columns = $report->columns;
        $filters = $report->filters ?? [];

        // Build query based on selected columns and filters
        // This is a simplified implementation
        return [
            'headers' => array_values($columns),
            'rows' => [
                // Dynamic data based on query results
            ],
            'summary' => [],
        ];
    }

    /**
     * Generate report file (CSV/Excel).
     */
    protected function generateReportFile(SalesReport $report, array $data): void
    {
        $filename = 'reports/' . $report->id . '_' . time() . '.csv';
        $content = $this->arrayToCsv($data);

        Storage::put($filename, $content);

        $report->update(['file_path' => $filename]);
    }

    /**
     * Convert array data to CSV format.
     */
    protected function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        // Write headers
        if (isset($data['headers'])) {
            fputcsv($output, $data['headers']);
        }

        // Write rows
        if (isset($data['rows'])) {
            foreach ($data['rows'] as $row) {
                fputcsv($output, $row);
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export report file.
     */
    public function exportReport(SalesReport $report): Response
    {
        if (!$report->file_path || !Storage::exists($report->file_path)) {
            abort(404, 'Report file not found');
        }

        $filename = $report->name . '_' . $report->generated_at->format('Y-m-d') . '.csv';

        return Storage::download($report->file_path, $filename);
    }
}
