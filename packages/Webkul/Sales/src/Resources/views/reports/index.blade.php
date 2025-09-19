@extends('sales::layouts.master')

@section('title')
    {{ trans('sales::app.reports.title') }}
@stop

@section('content')
    <div class="flex flex-col gap-4">
        {!! view_render_event('sales.reports.index.header.before') !!}

        <!-- Page Header -->
        <div class="flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <div class="flex flex-col gap-1">
                    <p class="text-xl font-bold leading-6 text-gray-800 dark:text-white">
                        {{ trans('sales::app.reports.title') }}
                    </p>

                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ trans('sales::app.reports.description') }}
                    </p>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <!-- Create Report Button -->
                    <div class="flex items-center gap-x-2.5">
                        <a
                            href="{{ route('admin.sales.reports.create') }}"
                            class="primary-button"
                        >
                            {{ trans('sales::app.reports.create-btn-title') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {!! view_render_event('sales.reports.index.header.after') !!}

        {!! view_render_event('sales.reports.index.datagrid.before') !!}

        <!-- DataGrid -->
        <x-admin::datagrid :src="route('admin.sales.reports.index')" ref="datagrid">
            <!-- DataGrid Shimmer -->
            <x-admin::shimmer.datagrid />
        </x-admin::datagrid>

        {!! view_render_event('sales.reports.index.datagrid.after') !!}
    </div>
@stop

@pushOnce('scripts')
    <script>
        // Add any custom JavaScript for the reports index page
    </script>
@endPushOnce
