@extends('sales::layouts.master')

@section('title')
    {{ trans('sales::app.targets.title') }}
@stop

@section('content')
    <div class="flex flex-col gap-4">
        {!! view_render_event('sales.targets.index.header.before') !!}

        <!-- Page Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="flex cursor-pointer items-center">
                    <x-admin::breadcrumbs name="sales.targets" />
                </div>

                <div class="text-xl font-bold dark:text-white">
                    {{ trans('sales::app.targets.title') }}
                </div>
            </div>

            <div class="flex items-center gap-x-2.5">
                <!-- Create Button -->
                @if (bouncer()->hasPermission('sales.targets.create'))
                    <div class="flex items-center gap-x-2.5">
                        <a
                            href="{{ route('admin.sales.targets.create') }}"
                            class="primary-button"
                        >
                            {{ trans('admin::app.layouts.create') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {!! view_render_event('sales.targets.index.header.after') !!}

        {!! view_render_event('sales.targets.index.datagrid.before') !!}

        <!-- DataGrid -->
        <x-admin::datagrid :src="route('admin.sales.targets.index')" ref="datagrid">
            <!-- DataGrid Shimmer -->
            <x-admin::shimmer.datagrid />
        </x-admin::datagrid>

        {!! view_render_event('sales.targets.index.datagrid.after') !!}
    </div>
@stop

@pushOnce('scripts')
    <script>
        // Add any custom JavaScript for the targets index page
    </script>
@endPushOnce
