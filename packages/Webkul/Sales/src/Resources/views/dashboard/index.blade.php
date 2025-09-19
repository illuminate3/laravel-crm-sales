@extends('sales::layouts.master')

@section('title')
    {{ trans('sales::app.dashboard.title') }}
@stop

@section('content')
    <div class="flex flex-col gap-4">
        {!! view_render_event('sales.dashboard.index.header.before') !!}

        <!-- Page Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="flex cursor-pointer items-center">
                    <x-admin::breadcrumbs name="sales.dashboard" />
                </div>

                <div class="text-xl font-bold dark:text-white">
                    {{ trans('sales::app.dashboard.title') }}
                </div>
            </div>

            <div class="flex items-center gap-x-2.5">
                <!-- Filter Controls -->
                <v-sales-dashboard-filters></v-sales-dashboard-filters>
            </div>
        </div>

        {!! view_render_event('sales.dashboard.index.header.after') !!}

        <!-- Sales Dashboard -->
        <v-sales-dashboard>
            <div class="flex flex-col gap-4">
                <!-- Shimmer for Overview Cards -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    @for ($i = 0; $i < 4; $i++)
                        <x-admin::shimmer.dashboard.index.over-all />
                    @endfor
                </div>

                <!-- Shimmer for Charts -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <x-admin::shimmer.dashboard.index.revenue />
                    <x-admin::shimmer.dashboard.index.revenue />
                </div>

                <!-- Shimmer for Quick Actions -->
                <x-admin::shimmer.dashboard.index.revenue />
            </div>
        </v-sales-dashboard>

        {!! view_render_event('sales.dashboard.index.content.after') !!}
    </div>
@stop

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-sales-dashboard-filters-template"
    >
        <div class="flex items-center gap-2">
            <!-- Period Filter -->
            <select
                v-model="filters.period"
                @change="applyFilters"
                class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
            >
                <option value="daily">{{ trans('admin::app.layouts.daily') }}</option>
                <option value="weekly">{{ trans('admin::app.layouts.weekly') }}</option>
                <option value="monthly">{{ trans('admin::app.layouts.monthly') }}</option>
                <option value="quarterly">{{ trans('admin::app.layouts.quarterly') }}</option>
            </select>

            <!-- Date Range -->
            <input
                type="date"
                v-model="filters.date_from"
                @change="applyFilters"
                class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
            />
            <span class="text-gray-500">to</span>
            <input
                type="date"
                v-model="filters.date_to"
                @change="applyFilters"
                class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
            />
        </div>
    </script>

    <script
        type="text/x-template"
        id="v-sales-dashboard-template"
    >
        <div v-if="isLoading">
            <slot></slot>
        </div>

        <div v-else class="flex flex-col gap-4">
            <!-- Overview Cards -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Targets -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ trans('sales::app.dashboard.total-targets') }}</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">@{{ overview.targets.total_targets }}</p>
                            <p class="text-xs text-gray-500">
                                @{{ overview.targets.active_targets }} active
                            </p>
                        </div>
                        <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                            <span class="icon-target text-xl text-blue-600 dark:text-blue-400"></span>
                        </div>
                    </div>
                </div>

                <!-- Achievement Rate -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ trans('sales::app.dashboard.performance-overview') }}</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">@{{ overview.targets.overall_achievement }}%</p>
                            <p class="text-xs text-gray-500">
                                @{{ overview.targets.achieved_targets }} achieved
                            </p>
                        </div>
                        <div class="rounded-full bg-green-100 p-3 dark:bg-green-900">
                            <span class="icon-leads text-xl text-green-600 dark:text-green-400"></span>
                        </div>
                    </div>
                </div>

                <!-- Total Target Amount -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ trans('admin::app.layouts.total-target') }}</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">@{{ formatCurrency(overview.targets.total_target_amount) }}</p>
                            <p class="text-xs text-gray-500">
                                Target amount
                            </p>
                        </div>
                        <div class="rounded-full bg-yellow-100 p-3 dark:bg-yellow-900">
                            <span class="icon-quote text-xl text-yellow-600 dark:text-yellow-400"></span>
                        </div>
                    </div>
                </div>

                <!-- Total Achieved -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ trans('admin::app.layouts.total-achieved') }}</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">@{{ formatCurrency(overview.targets.total_achieved_amount) }}</p>
                            <p class="text-xs text-gray-500">
                                Achieved amount
                            </p>
                        </div>
                        <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900">
                            <span class="icon-activity text-xl text-purple-600 dark:text-purple-400"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Targets Over Time -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                            {{ trans('admin::app.layouts.targets-over-time') }}
                        </h3>
                    </div>

                    <x-admin::charts.line
                        ::labels="targetsOverTimeLabels"
                        ::datasets="targetsOverTimeDatasets"
                        :aspect-ratio="2"
                    />
                </div>

                <!-- Performance Distribution -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                            {{ trans('admin::app.layouts.performance-distribution') }}
                        </h3>
                    </div>

                    <x-admin::charts.doughnut
                        ::labels="performanceDistributionLabels"
                        ::datasets="performanceDistributionDatasets"
                    />
                </div>
            </div>

            <!-- Quick Actions & Recent Activity -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Quick Actions -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">
                        {{ trans('admin::app.layouts.quick-actions') }}
                    </h3>

                    <div class="grid grid-cols-2 gap-3">
                        <a
                            href="{{ route('admin.sales.targets.create') }}"
                            class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                        >
                            <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                                <span class="icon-target text-blue-600 dark:text-blue-400"></span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-white">Create Target</p>
                                <p class="text-xs text-gray-500">Set new sales target</p>
                            </div>
                        </a>

                        <a
                            href="{{ route('admin.sales.performance.index') }}"
                            class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                        >
                            <div class="rounded-full bg-green-100 p-2 dark:bg-green-900">
                                <span class="icon-leads text-green-600 dark:text-green-400"></span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-white">View Performance</p>
                                <p class="text-xs text-gray-500">Check team performance</p>
                            </div>
                        </a>

                        <a
                            href="{{ route('admin.sales.reports.create') }}"
                            class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                        >
                            <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900">
                                <span class="icon-quote text-yellow-600 dark:text-yellow-400"></span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-white">Generate Report</p>
                                <p class="text-xs text-gray-500">Create sales report</p>
                            </div>
                        </a>

                        <a
                            href="{{ route('admin.sales.performance.leaderboard') }}"
                            class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                        >
                            <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                                <span class="icon-activity text-purple-600 dark:text-purple-400"></span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-white">Leaderboard</p>
                                <p class="text-xs text-gray-500">View top performers</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Targets -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">
                        {{ trans('admin::app.layouts.recent-targets') }}
                    </h3>

                    <div class="space-y-3">
                        <div
                            v-for="target in recentTargets"
                            :key="target.id"
                            class="flex items-center justify-between rounded-lg border border-gray-100 p-3 dark:border-gray-700"
                        >
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800 dark:text-white">
                                    @{{ target.name }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    @{{ target.assignee_name }} • @{{ target.period_type }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-800 dark:text-white">
                                    @{{ target.progress_percentage }}%
                                </p>
                                <div class="mt-1 h-1 w-16 rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        class="h-1 rounded-full bg-blue-600"
                                        :style="{ width: Math.min(100, target.progress_percentage) + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-sales-dashboard-filters', {
            template: '#v-sales-dashboard-filters-template',

            data() {
                return {
                    filters: {
                        period: 'monthly',
                        date_from: this.getDefaultDateFrom(),
                        date_to: this.getDefaultDateTo(),
                    },
                };
            },

            methods: {
                applyFilters() {
                    this.$emitter.emit('sales-dashboard-filter-updated', this.filters);
                },

                getDefaultDateFrom() {
                    const date = new Date();
                    date.setMonth(date.getMonth() - 6);
                    return date.toISOString().split('T')[0];
                },

                getDefaultDateTo() {
                    return new Date().toISOString().split('T')[0];
                },
            },

            mounted() {
                this.applyFilters();
            },
        });

        app.component('v-sales-dashboard', {
            template: '#v-sales-dashboard-template',

            data() {
                return {
                    isLoading: true,
                    overview: {
                        targets: {},
                        performance: {},
                    },
                    targetsOverTime: [],
                    performanceDistribution: [],
                    recentTargets: [],
                };
            },

            computed: {
                targetsOverTimeLabels() {
                    return this.targetsOverTime.map(item => item.period);
                },

                targetsOverTimeDatasets() {
                    return [{
                        label: 'Targets Created',
                        data: this.targetsOverTime.map(item => item.count),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                    }];
                },

                performanceDistributionLabels() {
                    return ['100%+', '75-99%', '50-74%', '<50%'];
                },

                performanceDistributionDatasets() {
                    return [{
                        data: [25, 35, 25, 15], // Sample data
                        backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444'],
                    }];
                },
            },

            mounted() {
                this.loadData({});

                this.$emitter.on('sales-dashboard-filter-updated', this.loadData);
            },

            methods: {
                loadData(filters) {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.sales.dashboard.stats') }}", {
                        params: { type: 'overview', ...filters }
                    }).then(response => {
                        this.overview = response.data;
                        this.isLoading = false;
                    });

                    // Load recent targets
                    this.$axios.get("{{ route('admin.sales.targets.index') }}", {
                        params: { per_page: 5, sort: 'created_at', order: 'desc' }
                    }).then(response => {
                        this.recentTargets = response.data.data;
                    });
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD'
                    }).format(amount);
                },
            },
        });
    </script>
@endPushOnce
