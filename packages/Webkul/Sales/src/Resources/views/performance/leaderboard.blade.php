@extends('sales::layouts.master')

@section('title')
    {{ trans('sales::app.performance.leaderboard') }}
@stop

@section('content')
    <div class="flex flex-col gap-4">
        {!! view_render_event('sales.performance.leaderboard.header.before') !!}

        <!-- Page Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="flex cursor-pointer items-center">
                    <x-admin::breadcrumbs name="sales.performance.leaderboard" />
                </div>

                <div class="text-xl font-bold dark:text-white">
                    {{ trans('sales::app.performance.leaderboard') }}
                </div>
            </div>

            <div class="flex items-center gap-x-2.5">
                <!-- Filter Controls -->
                <v-leaderboard-filters></v-leaderboard-filters>
            </div>
        </div>

        {!! view_render_event('sales.performance.leaderboard.header.after') !!}

        <!-- Leaderboard Content -->
        <v-leaderboard-dashboard>
            <div class="flex flex-col gap-4">
                <!-- Shimmer for Leaderboard -->
                <x-admin::shimmer.dashboard.index.revenue />
            </div>
        </v-leaderboard-dashboard>

        {!! view_render_event('sales.performance.leaderboard.content.after') !!}
    </div>
@stop

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-leaderboard-filters-template"
    >
        <div class="flex items-center gap-2">
            <!-- Type Filter -->
            <select
                v-model="filters.type"
                @change="applyFilters"
                class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
            >
                <option value="individual">Individual</option>
                <option value="team">Team</option>
                <option value="region">Region</option>
            </select>

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
            </select>

            <!-- Limit Filter -->
            <select
                v-model="filters.limit"
                @change="applyFilters"
                class="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 dark:bg-gray-800"
            >
                <option value="10">Top 10</option>
                <option value="20">Top 20</option>
                <option value="50">Top 50</option>
                <option value="100">Top 100</option>
            </select>
        </div>
    </script>

    <script
        type="text/x-template"
        id="v-leaderboard-dashboard-template"
    >
        <div v-if="isLoading">
            <slot></slot>
        </div>

        <div v-else class="flex flex-col gap-4">
            <!-- Top 3 Podium -->
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                    🏆 Top Performers
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4" v-if="leaderboard.length >= 3">
                    <!-- 2nd Place -->
                    <div class="order-1 md:order-1 text-center">
                        <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4 h-32 flex flex-col justify-end">
                            <div class="text-2xl mb-2">🥈</div>
                            <div class="font-semibold text-gray-800 dark:text-white">@{{ leaderboard[1]?.entity_name }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">@{{ leaderboard[1]?.achievement_percentage }}%</div>
                        </div>
                    </div>
                    
                    <!-- 1st Place -->
                    <div class="order-2 md:order-2 text-center">
                        <div class="bg-yellow-100 dark:bg-yellow-900 rounded-lg p-4 h-40 flex flex-col justify-end">
                            <div class="text-3xl mb-2">🥇</div>
                            <div class="font-bold text-gray-800 dark:text-white">@{{ leaderboard[0]?.entity_name }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">@{{ leaderboard[0]?.achievement_percentage }}%</div>
                        </div>
                    </div>
                    
                    <!-- 3rd Place -->
                    <div class="order-3 md:order-3 text-center">
                        <div class="bg-orange-100 dark:bg-orange-900 rounded-lg p-4 h-28 flex flex-col justify-end">
                            <div class="text-2xl mb-2">🥉</div>
                            <div class="font-semibold text-gray-800 dark:text-white">@{{ leaderboard[2]?.entity_name }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">@{{ leaderboard[2]?.achievement_percentage }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Full Leaderboard Table -->
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                        Complete Leaderboard
                    </h3>
                    
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        Showing @{{ leaderboard.length }} entries
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-600 dark:text-gray-300">
                                    Rank
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
                                :key="item.entity_id || item.id"
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
        app.component('v-leaderboard-filters', {
            template: '#v-leaderboard-filters-template',

            data() {
                return {
                    filters: {
                        type: 'individual',
                        period: 'monthly',
                        limit: 20,
                    },
                };
            },

            methods: {
                applyFilters() {
                    this.$emitter.emit('leaderboard-filter-updated', this.filters);
                },
            },

            mounted() {
                this.applyFilters();
            },
        });

        app.component('v-leaderboard-dashboard', {
            template: '#v-leaderboard-dashboard-template',

            data() {
                return {
                    isLoading: true,
                    leaderboard: [],
                };
            },

            mounted() {
                this.loadLeaderboard({});
                this.$emitter.on('leaderboard-filter-updated', this.loadLeaderboard);
            },

            methods: {
                loadLeaderboard(filters) {
                    this.isLoading = true;

                    this.$axios.get("{{ route('admin.sales.performance.leaderboard') }}", {
                        params: filters
                    }).then(response => {
                        this.leaderboard = response.data.leaderboard || [];
                        this.isLoading = false;
                    }).catch(error => {
                        console.error('Error loading leaderboard:', error);
                        this.isLoading = false;
                    });
                },

                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD'
                    }).format(amount || 0);
                },
            },
        });
    </script>
@endPushOnce
