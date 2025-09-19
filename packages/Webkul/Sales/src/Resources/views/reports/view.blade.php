@extends('sales::layouts.master')

@section('title')
    {{ trans('sales::app.reports.view-title') }} - {{ $report->name }}
@stop

@section('content')
    <div class="flex flex-col gap-4">
        {!! view_render_event('sales.reports.view.header.before', ['report' => $report]) !!}

        <!-- Page Header -->
        <div class="flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <div class="flex flex-col gap-1">
                    <p class="text-xl font-bold leading-6 text-gray-800 dark:text-white">
                        {{ $report->name }}
                    </p>

                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ trans('sales::app.reports.type') }}: {{ ucfirst($report->type) }} | 
                        {{ trans('sales::app.reports.created-on') }}: {{ $report->created_at->format('M d, Y') }}
                    </p>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <!-- Export Button -->
                    @if($report->status === 'completed')
                        <a
                            href="{{ route('admin.sales.reports.export', $report->id) }}"
                            class="secondary-button"
                        >
                            {{ trans('sales::app.reports.export') }}
                        </a>
                    @endif

                    <!-- Back Button -->
                    <a
                        href="{{ route('admin.sales.reports.index') }}"
                        class="transparent-button"
                    >
                        {{ trans('admin::app.layouts.back') }}
                    </a>
                </div>
            </div>
        </div>

        {!! view_render_event('sales.reports.view.header.after', ['report' => $report]) !!}

        <!-- Report Status -->
        <div class="bg-white dark:bg-gray-900 rounded-lg box-shadow">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    {{ trans('sales::app.reports.status') }}
                </p>
            </div>

            <div class="p-4">
                <div class="flex items-center gap-2">
                    @if($report->status === 'pending')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            {{ trans('sales::app.reports.status-pending') }}
                        </span>
                    @elseif($report->status === 'processing')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ trans('sales::app.reports.status-processing') }}
                        </span>
                    @elseif($report->status === 'completed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            {{ trans('sales::app.reports.status-completed') }}
                        </span>
                    @elseif($report->status === 'failed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            {{ trans('sales::app.reports.status-failed') }}
                        </span>
                    @endif

                    @if($report->status === 'processing')
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ trans('sales::app.reports.processing-message') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Report Details -->
        <div class="bg-white dark:bg-gray-900 rounded-lg box-shadow">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    {{ trans('sales::app.reports.details') }}
                </p>
            </div>

            <div class="p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('sales::app.reports.date-range') }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($report->date_from)->format('M d, Y') }} - 
                            {{ \Carbon\Carbon::parse($report->date_to)->format('M d, Y') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ trans('sales::app.reports.created-by') }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $report->creator->name ?? 'System' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        @if($report->status === 'completed' && $report->data)
            <div class="bg-white dark:bg-gray-900 rounded-lg box-shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                        {{ trans('sales::app.reports.data') }}
                    </p>
                </div>

                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    @if(isset($report->data['headers']))
                                        @foreach($report->data['headers'] as $header)
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                {{ $header }}
                                            </th>
                                        @endforeach
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @if(isset($report->data['rows']))
                                    @foreach($report->data['rows'] as $row)
                                        <tr>
                                            @foreach($row as $cell)
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $cell }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {!! view_render_event('sales.reports.view.content.after', ['report' => $report]) !!}
    </div>
@stop

@pushOnce('scripts')
    <script>
        // Auto-refresh for pending/processing reports
        @if(in_array($report->status, ['pending', 'processing']))
            setTimeout(() => {
                window.location.reload();
            }, 10000); // Refresh every 10 seconds
        @endif
    </script>
@endPushOnce
