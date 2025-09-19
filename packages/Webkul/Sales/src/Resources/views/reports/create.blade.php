@extends('sales::layouts.master')

@section('title')
    {{ trans('sales::app.reports.create-title') }}
@stop

@section('content')
    <v-sales-report-form
        :report-types="{{ json_encode($reportTypes) }}"
        :available-columns="{{ json_encode($availableColumns) }}"
        :action="'{{ route('admin.sales.reports.store') }}'"
    >
        <x-admin::shimmer.form.index />
    </v-sales-report-form>
@stop

@pushOnce('scripts')
    <script type="text/x-template" id="v-sales-report-form-template">
        <div class="flex flex-col gap-4">
            <!-- Page Header -->
            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col gap-1">
                        <p class="text-xl font-bold leading-6 text-gray-800 dark:text-white">
                            {{ trans('sales::app.reports.create-title') }}
                        </p>

                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ trans('sales::app.reports.create-description') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-x-2.5">
                        <a
                            href="{{ route('admin.sales.reports.index') }}"
                            class="transparent-button"
                        >
                            {{ trans('sales::app.common.back') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Report Form -->
            <x-admin::form
                :action="action"
                method="POST"
                enctype="multipart/form-data"
                v-slot="{ meta, errors, handleSubmit }"
            >
                <div class="flex flex-col gap-4">
                    <!-- Basic Information -->
                    <div class="bg-white dark:bg-gray-900 rounded-lg box-shadow">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                {{ trans('sales::app.reports.basic-info') }}
                            </p>
                        </div>

                        <div class="p-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <!-- Report Name -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.reports.name') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="name"
                                        v-model="form.name"
                                        rules="required"
                                        :label="trans('sales::app.reports.name')"
                                        :placeholder="trans('sales::app.reports.name-placeholder')"
                                    />

                                    <x-admin::form.control-group.error control-name="name" />
                                </x-admin::form.control-group>

                                <!-- Report Type -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.reports.type') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="type"
                                        v-model="form.type"
                                        rules="required"
                                        :label="trans('sales::app.reports.type')"
                                    >
                                        <option value="">{{ trans('sales::app.reports.select-type') }}</option>
                                        <option
                                            v-for="(reportType, key) in reportTypes"
                                            :key="key"
                                            :value="key"
                                        >
                                            @{{ reportType.name }}
                                        </option>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="type" />
                                </x-admin::form.control-group>
                            </div>

                            <!-- Filter Period -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mt-4">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        {{ trans('sales::app.reports.filter-period') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="filter_period"
                                        v-model="form.filter_period"
                                        :label="trans('sales::app.reports.filter-period')"
                                        @change="updateDateRange"
                                    >
                                        <option value="">{{ trans('sales::app.common.select') }}</option>
                                        <option value="weekly">{{ trans('sales::app.reports.weekly') }}</option>
                                        <option value="monthly">{{ trans('sales::app.common.monthly') }}</option>
                                        <option value="quarterly">{{ trans('sales::app.reports.quarterly') }}</option>
                                        <option value="half_yearly">{{ trans('sales::app.reports.half-yearly') }}</option>
                                        <option value="yearly">{{ trans('sales::app.reports.yearly') }}</option>
                                        <option value="custom">{{ trans('sales::app.common.custom') }}</option>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="filter_period" />
                                </x-admin::form.control-group>

                                <!-- Date From -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.reports.date-from') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="date"
                                        name="date_from"
                                        v-model="form.date_from"
                                        rules="required"
                                        :label="trans('sales::app.reports.date-from')"
                                    />

                                    <x-admin::form.control-group.error control-name="date_from" />
                                </x-admin::form.control-group>

                                <!-- Date To -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.reports.date-to') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="date"
                                        name="date_to"
                                        v-model="form.date_to"
                                        rules="required"
                                        :label="trans('sales::app.reports.date-to')"
                                    />

                                    <x-admin::form.control-group.error control-name="date_to" />
                                </x-admin::form.control-group>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center gap-x-2.5">
                        <button
                            type="submit"
                            class="primary-button"
                            :disabled="isLoading"
                        >
                            {{ trans('sales::app.reports.create-btn-title') }}
                        </button>
                    </div>
                </div>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-sales-report-form', {
            template: '#v-sales-report-form-template',

            props: {
                reportTypes: {
                    type: Object,
                    required: true,
                },

                availableColumns: {
                    type: Object,
                    required: true,
                },

                action: {
                    type: String,
                    required: true,
                },
            },

            data() {
                return {
                    form: {
                        name: '',
                        type: '',
                        filter_period: '',
                        date_from: '',
                        date_to: '',
                        filters: {},
                        columns: [],
                        grouping: [],
                        sorting: [],
                    },

                    isLoading: false,
                };
            },

            mounted() {
                // Set default date range (last 30 days)
                const today = new Date();
                const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));

                this.form.date_to = today.toISOString().split('T')[0];
                this.form.date_from = thirtyDaysAgo.toISOString().split('T')[0];
            },

            methods: {
                updateDateRange() {
                    const today = new Date();
                    let startDate = new Date();

                    switch (this.form.filter_period) {
                        case 'weekly':
                            startDate.setDate(today.getDate() - 7);
                            break;
                        case 'monthly':
                            startDate.setMonth(today.getMonth() - 1);
                            break;
                        case 'quarterly':
                            startDate.setMonth(today.getMonth() - 3);
                            break;
                        case 'half_yearly':
                            startDate.setMonth(today.getMonth() - 6);
                            break;
                        case 'yearly':
                            startDate.setFullYear(today.getFullYear() - 1);
                            break;
                        case 'custom':
                            // Don't auto-update for custom
                            return;
                        default:
                            return;
                    }

                    this.form.date_from = startDate.toISOString().split('T')[0];
                    this.form.date_to = today.toISOString().split('T')[0];
                },
            },
        });
    </script>
@endPushOnce
