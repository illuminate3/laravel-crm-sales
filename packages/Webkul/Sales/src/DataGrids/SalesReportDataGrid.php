<?php

namespace Webkul\Sales\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class SalesReportDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('sales_reports')
            ->addSelect(
                'sales_reports.id',
                'sales_reports.name',
                'sales_reports.type',
                'sales_reports.status',
                'sales_reports.date_from',
                'sales_reports.date_to',
                'sales_reports.generated_at',
                'sales_reports.is_public',
                'sales_reports.is_scheduled',
                'sales_reports.created_at',
                'creator.name as created_by_name'
            )
            ->leftJoin('users as creator', 'sales_reports.created_by', '=', 'creator.id');

        $this->addFilter('id', 'sales_reports.id');
        $this->addFilter('name', 'sales_reports.name');
        $this->addFilter('type', 'sales_reports.type');
        $this->addFilter('status', 'sales_reports.status');

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
            'index'      => 'type',
            'label'      => trans('sales::app.reports.type'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $typeLabels = [
                    'commission'      => trans('sales::app.reports.commission'),
                    'yoy_growth'      => trans('sales::app.reports.yoy-growth'),
                    'pipeline_health' => trans('sales::app.reports.pipeline-health'),
                    'custom'          => trans('sales::app.reports.custom'),
                ];

                return $typeLabels[$row->type] ?? ucfirst(str_replace('_', ' ', $row->type));
            },
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('sales::app.reports.status'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $statusLabels = [
                    'pending'    => '<span class="label-pending">' . trans('sales::app.reports.status-pending') . '</span>',
                    'processing' => '<span class="label-processing">' . trans('sales::app.reports.status-processing') . '</span>',
                    'completed'  => '<span class="label-completed">' . trans('sales::app.reports.status-completed') . '</span>',
                    'failed'     => '<span class="label-failed">' . trans('sales::app.reports.status-failed') . '</span>',
                ];

                return $statusLabels[$row->status] ?? ucfirst($row->status);
            },
        ]);

        $this->addColumn([
            'index'    => 'date_from',
            'label'    => trans('sales::app.reports.date-from'),
            'type'     => 'date',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'    => 'date_to',
            'label'    => trans('sales::app.reports.date-to'),
            'type'     => 'date',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'    => 'generated_at',
            'label'    => 'Generated At',
            'type'     => 'datetime',
            'sortable' => true,
            'closure'  => function ($row) {
                return $row->generated_at ? date('M d, Y H:i', strtotime($row->generated_at)) : '-';
            },
        ]);

        $this->addColumn([
            'index'    => 'created_by_name',
            'label'    => trans('sales::app.reports.created-by'),
            'type'     => 'string',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'    => 'is_public',
            'label'    => 'Public',
            'type'     => 'boolean',
            'sortable' => true,
            'closure'  => function ($row) {
                return $row->is_public ? 'Yes' : 'No';
            },
        ]);

        $this->addColumn([
            'index'    => 'created_at',
            'label'    => trans('admin::app.datagrid.created-at'),
            'type'     => 'datetime',
            'sortable' => true,
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
        $this->addAction([
            'icon'   => 'icon-view',
            'title'  => trans('sales::app.acl.view'),
            'method' => 'GET',
            'url'    => function ($row) {
                return route('admin.sales.reports.view', $row->id);
            },
        ]);

        if (bouncer()->hasPermission('sales.reports.export')) {
            $this->addAction([
                'icon'      => 'icon-download',
                'title'     => trans('sales::app.acl.export'),
                'method'    => 'GET',
                'url'       => function ($row) {
                    return route('admin.sales.reports.export', $row->id);
                },
                'condition' => function ($row) {
                    return $row->status === 'completed';
                },
            ]);
        }

        if (bouncer()->hasPermission('sales.reports.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('sales::app.acl.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.sales.reports.delete', $row->id);
                },
            ]);
        }
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('sales.reports.delete')) {
            $this->addMassAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.datagrid.delete'),
                'method' => 'POST',
                'url'    => route('admin.sales.reports.mass_delete'),
            ]);
        }
    }
}
