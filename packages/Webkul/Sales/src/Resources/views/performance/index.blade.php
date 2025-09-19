@extends('sales::layouts.master')

@section('title')
    {{ trans('sales::app.performance.title') }}
@stop

@section('content')
    <div class="flex flex-col gap-4">
        {!! view_render_event('sales.performance.index.header.before') !!}

        <!-- Page Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="flex cursor-pointer items-center">
                    <x-admin::breadcrumbs name="sales.performance" />
                </div>

                <div class="text-xl font-bold dark:text-white">
                    {{ trans('sales::app.performance.title') }}
                </div>
            </div>

            <div class="flex items-center gap-x-2.5">
                <!-- Filter Controls -->
                <v-performance-filters></v-performance-filters>
            </div>
        </div>

        {!! view_render_event('sales.performance.index.header.after') !!}

        <!-- Performance Dashboard -->
        <v-performance-dashboard>
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

                <!-- Shimmer for Leaderboard -->
                <x-admin::shimmer.dashboard.index.revenue />
            </div>
        </v-performance-dashboard>

        {!! view_render_event('sales.performance.index.content.after') !!}
    </div>
@stop

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-performance-filters-template"
    >
        <div class="flex items-center gap-2">
            <!-- Period Filter -->
            <select
                v-model="filters.period"
                @change="applyFilters"
                class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
            >
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="yearly">Yearly</option>
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
        id="v-performance-dashboard-template"
    >
        <div v-if="isLoading">
            <slot></slot>
        </div>

        <div v-else class="flex flex-col gap-4">
            <!-- Overview Cards -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Achievement -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ trans('sales::app.performance.achievement-rate') }}</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">@{{ summary.average_achievement }}%</p>
                        </div>
                        <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                            <span class="icon-target text-xl text-blue-600 dark:text-blue-400"></span>
                        </div>
                    </div>
                </div>

                <!-- Conversion Rate -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Conversion Rate</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">@{{ summary.average_conversion }}%</p>
                        </div>
                        <div class="rounded-full bg-green-100 p-3 dark:bg-green-900">
                            <span class="icon-leads text-xl text-green-600 dark:text-green-400"></span>
                        </div>
                    </div>
                </div>

                <!-- Total Target -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Total Target</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">@{{ formatCurrency(summary.total_target_amount) }}</p>
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
                            <p class="text-sm text-gray-600 dark:text-gray-300">Total Achieved</p>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">@{{ formatCurrency(summary.total_achieved_amount) }}</p>
                        </div>
                        <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900">
                            <span class="icon-activity text-xl text-purple-600 dark:text-purple-400"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Target vs Actual Chart -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                            {{ trans('sales::app.performance.target-vs-actual') }}
                        </h3>

                        <div class="flex items-center gap-x-2">
                            <select
                                v-model="targetVsActualPeriod"
                                @change="loadTargetVsActual"
                                class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
                            >
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>

                            <select
                                v-model="targetVsActualType"
                                @change="loadTargetVsActual"
                                class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
                            >
                                <option value="individual">Individual</option>
                                <option value="total">Total</option>
                            </select>
                        </div>
                    </div>

                    <x-admin::charts.bar
                        ::labels="targetVsActualLabels"
                        ::datasets="targetVsActualDatasets"
                        :aspect-ratio="2"
                    />
                </div>

                <!-- Performance Trends -->
                <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                            {{ trans('sales::app.performance.progress') }}
                        </h3>

                        <div class="flex items-center gap-x-2">
                            <select
                                v-model="trendsPeriod"
                                @change="loadTrends"
                                class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
                            >
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>

                            <select
                                v-model="trendsType"
                                @change="loadTrends"
                                class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
                            >
                                <option value="individual">Individual</option>
                                <option value="total">Total</option>
                            </select>
                        </div>
                    </div>

                    <x-admin::charts.line
                        ::labels="trendsLabels"
                        ::datasets="trendsDatasets"
                        :aspect-ratio="2"
                    />
                </div>
            </div>

            <!-- Leaderboard -->
            <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                        {{ trans('sales::app.performance.leaderboard') }}
                    </h3>

                    <div class="flex items-center gap-2">
                        <select
                            v-model="leaderboardType"
                            @change="loadLeaderboard"
                            class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
                        >
                            <option value="individual">{{ trans('sales::app.performance.individual') }}</option>
                            <option value="team">{{ trans('sales::app.performance.team') }}</option>
                            <option value="region">{{ trans('sales::app.performance.region') }}</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600 dark:text-gray-300">
                                    {{ trans('sales::app.performance.rank') }}
                                </th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600 dark:text-gray-300">
                                    Name
                                </th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600 dark:text-gray-300">
                                    Achievement Rate
                                </th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600 dark:text-gray-300">
                                    Score
                                </th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600 dark:text-gray-300">
                                    Target
                                </th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600 dark:text-gray-300">
                                    Achieved
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in leaderboard"
                                :key="item.id"
                                class="border-b border-gray-100 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <span
                                            class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': index === 0,
                                                'bg-gray-100 text-gray-800': index === 1,
                                                'bg-orange-100 text-orange-800': index === 2,
                                                'bg-blue-100 text-blue-800': index > 2
                                            }"
                                        >
                                            @{{ index + 1 }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800 dark:text-white">
                                        @{{ item.entity_name }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <div class="mr-2 h-2 w-16 rounded-full bg-gray-200 dark:bg-gray-700">
                                            <div
                                                class="h-2 rounded-full bg-blue-600"
                                                :style="{ width: Math.min(100, item.achievement_percentage) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-sm text-gray-600 dark:text-gray-300">
                                            @{{ item.achievement_percentage }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm font-medium text-gray-800 dark:text-white">
                                        @{{ item.score }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                        @{{ formatCurrency(item.target_amount) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                        @{{ formatCurrency(item.achieved_amount) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-performance-filters', {
            template: '#v-performance-filters-template',

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
                    this.$emitter.emit('performance-filter-updated', this.filters);
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

        app.component('v-performance-dashboard', {
            template: '#v-performance-dashboard-template',

            data() {
                return {
                    isLoading: true,
                    summary: {},
                    targetVsActual: [],
                    trends: [],
                    leaderboard: [],
                    leaderboardType: 'individual',
                    targetVsActualPeriod: 'monthly',
                    targetVsActualType: 'individual',
                    trendsPeriod: 'monthly',
                    trendsType: 'individual',
                };
            },

            computed: {
                targetVsActualLabels() {
                    return this.targetVsActual.map(item => item.entity_name);
                },

                targetVsActualDatasets() {
                    return [{
                        label: 'Target',
                        data: this.targetVsActual.map(item => parseFloat(item.target_amount) || 0),
                        backgroundColor: '#E5E7EB',
                        barThickness: 24,
                    }, {
                        label: 'Achieved',
                        data: this.targetVsActual.map(item => parseFloat(item.achieved_amount) || 0),
                        backgroundColor: '#3B82F6',
                        barThickness: 24,
                    }];
                },

                trendsLabels() {
                    return this.trends.map(item => item.period_start);
                },

                trendsDatasets() {
                    return [{
                        label: 'Achievement %',
                        data: this.trends.map(item => parseFloat(item.avg_value) || 0),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                    }];
                },
            },

                mounted() {
                    this.loadData({});
                    this.loadLeaderboard();

                    this.$emitter.on('performance-filter-updated', this.loadData);
                },

                methods: {
                    loadData(filters) {
                        this.isLoading = true;

                        this.loadOverview(filters);
                        this.loadTargetVsActual(filters);
                        this.loadTrends(filters);
                    },

                    loadOverview(filters) {
                        this.$axios.get("{{ route('admin.sales.performance.stats') }}", {
                            params: { type: 'overview', ...filters }
                        }).then(response => {
                            this.summary = response.data;
                        }).catch(error => {
                            console.error('Error loading overview:', error);
                        });
                    },

                    loadTargetVsActual(filters) {
                        this.$axios.get("{{ route('admin.sales.performance.stats') }}", {
                            params: {
                                type: 'target-vs-actual',
                                period: this.targetVsActualPeriod,
                                view_type: this.targetVsActualType,
                                ...filters
                            }
                        }).then(response => {
                            this.targetVsActual = response.data.chart_data || [];
                        }).catch(error => {
                            console.error('Error loading target vs actual:', error);
                            this.targetVsActual = [];
                        });
                    },

                    loadTrends(filters) {
                        this.$axios.get("{{ route('admin.sales.performance.stats') }}", {
                            params: {
                                type: 'trends',
                                period: this.trendsPeriod,
                                view_type: this.trendsType,
                                ...filters
                            }
                        }).then(response => {
                            this.trends = response.data.trends || [];
                        }).catch(error => {
                            console.error('Error loading trends:', error);
                            this.trends = [];
                        }).finally(() => {
                            this.isLoading = false;
                        });
                    },

                loadLeaderboard() {
                    this.$axios.get("{{ route('admin.sales.performance.leaderboard') }}", {
                        params: { type: this.leaderboardType }
                    }).then(response => {
                        this.leaderboard = response.data.leaderboard;
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
