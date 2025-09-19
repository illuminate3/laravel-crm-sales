<?php

namespace Webkul\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Sales\DataGrids\SalesTargetDataGrid;
use Webkul\Sales\Models\SalesTarget;
use Webkul\Sales\Models\SalesTeam;
use Webkul\Sales\Models\SalesRegion;
use Webkul\Sales\Repositories\SalesTargetRepository;
use Webkul\User\Models\User;

class TargetController extends Controller
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
     * Display a listing of the resource.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(SalesTargetDataGrid::class)->toJson();
        }

        return view('sales::targets.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $assigneeOptions = $this->getAssigneeOptions();

        return view('sales::targets.create', compact('assigneeOptions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): RedirectResponse|JsonResponse
    {
        $this->validate(request(), [
            'name'                  => 'required|string|max:255',
            'target_amount'         => 'required|numeric|min:0',
            'target_for_new_logo'   => 'nullable|integer|min:0',
            'crs_and_renewals_obv'  => 'nullable|numeric|min:0',
            'financial_year'        => 'nullable|string',
            'quarter'               => 'nullable|in:Q1,Q2,Q3,Q4',
            'assignee_type'         => 'required|in:individual,team,region',
            'assignee_id'    => 'required|integer',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'period_type'    => 'required|in:daily,weekly,monthly,quarterly,half_yearly,annual,custom',
            'status'         => 'in:active,completed,paused,cancelled',
        ]);

        $data = request()->all();
        $data['created_by'] = auth()->id();
        $data['status'] = $data['status'] ?? 'active'; // Default status
        $data['assignee_name'] = $this->getAssigneeName($data['assignee_type'], $data['assignee_id']);

        try {
            $target = $this->salesTargetRepository->create($data);

            $message = trans('sales::app.targets.create-success');

            if (request()->ajax()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => $message,
                    'data'    => $target,
                ]);
            }

            session()->flash('success', $message);
            return redirect()->route('admin.sales.targets.index');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'An error occurred while creating the target: ' . $e->getMessage(),
                ], 500);
            }

            session()->flash('error', 'An error occurred while creating the target.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $target = $this->salesTargetRepository->findOrFail($id);
        $assigneeOptions = $this->getAssigneeOptions();

        return view('sales::targets.edit', compact('target', 'assigneeOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id): RedirectResponse
    {
        $this->validate(request(), [
            'name'                          => 'required|string|max:255',
            'target_amount'                 => 'required|numeric|min:0',
            'target_for_new_logo'           => 'nullable|integer|min:0',
            'crs_and_renewals_obv'          => 'nullable|numeric|min:0',
            'financial_year'                => 'nullable|string',
            'quarter'                       => 'nullable|in:Q1,Q2,Q3,Q4',
            'achieved_amount'               => 'nullable|numeric|min:0',
            'achieved_new_logos'            => 'nullable|integer|min:0',
            'achieved_crs_and_renewals_obv' => 'nullable|numeric|min:0',
            'assignee_type'                 => 'required|in:individual,team,region',
            'assignee_id'    => 'required|integer',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'period_type'    => 'required|in:daily,weekly,monthly,quarterly,half_yearly,annual,custom',
            'status'         => 'required|in:active,completed,paused,cancelled',
        ]);

        $target = $this->salesTargetRepository->findOrFail($id);
        $data = request()->all();
        $data['updated_by'] = auth()->id();
        $data['assignee_name'] = $this->getAssigneeName($data['assignee_type'], $data['assignee_id']);

        // Track changes for audit trail
        $this->trackChanges($target, $data);

        $this->salesTargetRepository->update($data, $id);

        session()->flash('success', trans('sales::app.targets.update-success'));

        return redirect()->route('admin.sales.targets.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->salesTargetRepository->delete($id);

        return new JsonResponse([
            'message' => trans('sales::app.targets.delete-success'),
        ]);
    }

    /**
     * Mass update targets.
     */
    public function massUpdate(): JsonResponse
    {
        $data = request()->validate([
            'indices' => 'required|array',
            'value'   => 'required|string',
        ]);

        foreach ($data['indices'] as $id) {
            $this->salesTargetRepository->update(['status' => $data['value']], $id);
        }

        return new JsonResponse([
            'message' => trans('admin::app.datagrid.mass-ops.update-success'),
        ]);
    }

    /**
     * Mass delete targets.
     */
    public function massDestroy(): JsonResponse
    {
        $data = request()->validate([
            'indices' => 'required|array',
        ]);

        foreach ($data['indices'] as $id) {
            $this->salesTargetRepository->delete($id);
        }

        return new JsonResponse([
            'message' => trans('admin::app.datagrid.mass-ops.delete-success'),
        ]);
    }

    /**
     * Get assignee options for dropdowns.
     */
    protected function getAssigneeOptions(): array
    {
        try {
            return [
                'individuals' => User::select('id', 'name')->get(),
                'teams'       => SalesTeam::active()->select('id', 'name')->get(),
                'regions'     => SalesRegion::active()->select('id', 'name')->get(),
            ];
        } catch (\Exception $e) {
            // Fallback if tables don't exist yet
            return [
                'individuals' => User::select('id', 'name')->get(),
                'teams'       => collect([]),
                'regions'     => collect([]),
            ];
        }
    }

    /**
     * Get assignee name based on type and id.
     */
    protected function getAssigneeName(string $type, int $id): string
    {
        switch ($type) {
            case 'individual':
                return User::find($id)?->name ?? '';
            case 'team':
                return SalesTeam::find($id)?->name ?? '';
            case 'region':
                return SalesRegion::find($id)?->name ?? '';
            default:
                return '';
        }
    }

    /**
     * Track changes for audit trail.
     */
    protected function trackChanges(SalesTarget $target, array $newData): void
    {
        $changes = [];
        $trackableFields = ['target_amount', 'start_date', 'end_date', 'assignee_type', 'assignee_id', 'status'];

        foreach ($trackableFields as $field) {
            if (isset($newData[$field]) && $target->$field != $newData[$field]) {
                $changes[$field] = [
                    'old' => $target->$field,
                    'new' => $newData[$field],
                ];
            }
        }

        if (!empty($changes)) {
            foreach ($changes as $field => $change) {
                $target->adjustments()->create([
                    'adjustment_type' => $field,
                    'old_value'       => [$field => $change['old']],
                    'new_value'       => [$field => $change['new']],
                    'reason'          => request('adjustment_reason', 'Updated via form'),
                    'adjusted_by'     => auth()->id(),
                    'adjusted_at'     => now(),
                ]);
            }
        }
    }
}
