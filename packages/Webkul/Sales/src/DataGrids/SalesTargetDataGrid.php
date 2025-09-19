<?php

namespace Webkul\Sales\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class SalesTargetDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('sales_targets')
            ->addSelect(
                'sales_targets.id',
                'sales_targets.name',
                'sales_targets.target_amount',
                'sales_targets.target_for_new_logo',
                'sales_targets.crs_and_renewals_obv',
                'sales_targets.financial_year',
                'sales_targets.quarter',
                'sales_targets.achieved_amount',
                'sales_targets.progress_percentage',
                'sales_targets.assignee_type',
                'sales_targets.assignee_name',
                'sales_targets.start_date',
                'sales_targets.end_date',
                'sales_targets.status',
                'sales_targets.created_at',
                'creator.name as created_by_name'
            )
            ->leftJoin('users as creator', 'sales_targets.created_by', '=', 'creator.id');

        $this->addFilter('id', 'sales_targets.id');
        $this->addFilter('name', 'sales_targets.name');
        $this->addFilter('assignee_type', 'sales_targets.assignee_type');
        $this->addFilter('status', 'sales_targets.status');
        $this->addFilter('financial_year', 'sales_targets.financial_year');
        $this->addFilter('quarter', 'sales_targets.quarter');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'    => 'id',
            'label'    => trans('sales::app.common.id'),
            'type'     => 'string',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('sales::app.common.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'    => 'target_amount',
            'label'    => trans('sales::app.common.target-amount'),
            'type'     => 'string',
            'sortable' => true,
            'closure'  => function ($row) {
                return '$' . number_format($row->target_amount, 2);
            },
        ]);

        $this->addColumn([
            'index'    => 'target_for_new_logo',
            'label'    => trans('sales::app.targets.target-for-new-logo'),
            'type'     => 'string',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'    => 'crs_and_renewals_obv',
            'label'    => trans('sales::app.targets.crs-and-renewals-obv'),
            'type'     => 'string',
            'sortable' => true,
            'closure'  => function ($row) {
                return '$' . number_format($row->crs_and_renewals_obv, 2);
            },
        ]);

        $this->addColumn([
            'index'      => 'financial_year',
            'label'      => trans('sales::app.targets.financial-year'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'quarter',
            'label'      => trans('sales::app.targets.quarter'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'    => 'achieved_amount',
            'label'    => trans('sales::app.common.achieved-amount'),
            'type'     => 'string',
            'sortable' => true,
            'closure'  => function ($row) {
                return '$' . number_format($row->achieved_amount, 2);
            },
        ]);

        $this->addColumn([
            'index'    => 'progress_percentage',
            'label'    => trans('sales::app.common.progress-percentage'),
            'type'     => 'string',
            'sortable' => true,
            'closure'  => function ($row) {
                return number_format($row->progress_percentage, 1) . '%';
            },
        ]);

        $this->addColumn([
            'index'      => 'assignee_type',
            'label'      => trans('sales::app.common.assignee-type'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                return ucfirst($row->assignee_type);
            },
        ]);

        $this->addColumn([
            'index'      => 'assignee_name',
            'label'      => trans('sales::app.common.assignee-name'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'    => 'start_date',
            'label'    => trans('sales::app.common.start-date'),
            'type'     => 'date',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'    => 'end_date',
            'label'    => trans('sales::app.common.end-date'),
            'type'     => 'date',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('sales::app.common.status'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $statusLabels = [
                    'active'    => '<span class="label-active">' . trans('sales::app.targets.active') . '</span>',
                    'completed' => '<span class="label-completed">' . trans('sales::app.targets.completed') . '</span>',
                    'paused'    => '<span class="label-paused">' . trans('sales::app.targets.paused') . '</span>',
                    'cancelled' => '<span class="label-cancelled">Cancelled</span>',
                ];

                return $statusLabels[$row->status] ?? ucfirst($row->status);
            },
        ]);

        $this->addColumn([
            'index'    => 'created_at',
            'label'    => trans('sales::app.common.created-at'),
            'type'     => 'datetime',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'    => 'updated_at',
            'label'    => trans('sales::app.common.updated-at'),
            'type'     => 'datetime',
            'sortable' => true,
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('sales.targets.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('sales::app.acl.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.sales.targets.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('sales.targets.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('sales::app.acl.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.sales.targets.delete', $row->id);
                },
            ]);
        }
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('sales.targets.delete')) {
            $this->addMassAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.datagrid.delete'),
                'method' => 'POST',
                'url'    => route('admin.sales.targets.mass_delete'),
            ]);
        }

        $this->addMassAction([
            'icon'    => 'icon-edit',
            'title'   => trans('admin::app.datagrid.update-status'),
            'method'  => 'POST',
            'url'     => route('admin.sales.targets.mass_update'),
            'options' => [
                [
                    'label' => trans('sales::app.targets.active'),
                    'value' => 'active',
                ],
                [
                    'label' => trans('sales::app.targets.completed'),
                    'value' => 'completed',
                ],
                [
                    'label' => trans('sales::app.targets.paused'),
                    'value' => 'paused',
                ],
                [
                    'label' => 'Cancelled',
                    'value' => 'cancelled',
                ],
            ],
        ]);
    }
}
