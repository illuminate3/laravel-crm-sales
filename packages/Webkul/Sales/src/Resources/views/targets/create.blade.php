@extends('sales::layouts.master')

@section('title')
    {{ trans('sales::app.targets.create-title') }}
@stop

@section('content')
    <v-sales-target-form
        :assignee-options="{{ json_encode($assigneeOptions) }}"
        action="{{ route('admin.sales.targets.store') }}"
        csrf-token="{{ csrf_token() }}"
    >
        <x-admin::shimmer.form.index />
    </v-sales-target-form>
@stop

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-sales-target-form-template"
    >
        <form @submit.prevent="submit">
            <div class="flex flex-col gap-4">
                <!-- Page Header -->
                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    <div class="flex flex-col gap-2">
                        <div class="flex cursor-pointer items-center">
                            <x-admin::breadcrumbs name="sales.targets.create" />
                        </div>

                        <div class="text-xl font-bold dark:text-white">
                            {{ trans('sales::app.targets.create-title') }}
                        </div>
                    </div>

                    <div class="flex items-center gap-x-2.5">
                        <a
                            href="{{ route('admin.sales.targets.index') }}"
                            class="transparent-button"
                        >
                            {{ trans('sales::app.common.back') }}
                        </a>

                        <button
                            type="submit"
                            class="primary-button"
                            :disabled="isLoading"
                        >
                            {{ trans('sales::app.common.save') }}
                        </button>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="flex gap-2.5 max-xl:flex-wrap">
                    <!-- Left Panel -->
                    <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                        <!-- Basic Information -->
                        <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                                {{ trans('sales::app.common.general') }}
                            </p>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Name -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.targets.name') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="name"
                                        v-model="form.name"
                                        rules="required"
                                        :label="trans('sales::app.targets.name')"
                                        :placeholder="trans('sales::app.targets.name')"
                                    />

                                    <x-admin::form.control-group.error control-name="name" />
                                </x-admin::form.control-group>

                                <!-- Target Amount -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.targets.target-amount') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="number"
                                        name="target_amount"
                                        v-model="form.target_amount"
                                        rules="required|min_value:0"
                                        step="0.01"
                                        :label="trans('sales::app.targets.target-amount')"
                                        :placeholder="trans('sales::app.targets.target-amount')"
                                    />

                                    <x-admin::form.control-group.error control-name="target_amount" />
                                </x-admin::form.control-group>

                                <!-- Target for New Logo -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        {{ trans('sales::app.targets.target-for-new-logo') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="number"
                                        name="target_for_new_logo"
                                        v-model="form.target_for_new_logo"
                                        rules="integer"
                                        :label="trans('sales::app.targets.target-for-new-logo')"
                                        :placeholder="trans('sales::app.targets.target-for-new-logo')"
                                    />

                                    <x-admin::form.control-group.error control-name="target_for_new_logo" />
                                </x-admin::form.control-group>

                                <!-- CRs and Renewals OBV -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        {{ trans('sales::app.targets.crs-and-renewals-obv') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="number"
                                        name="crs_and_renewals_obv"
                                        v-model="form.crs_and_renewals_obv"
                                        rules="decimal"
                                        step="0.01"
                                        :label="trans('sales::app.targets.crs-and-renewals-obv')"
                                        :placeholder="trans('sales::app.targets.crs-and-renewals-obv')"
                                    />

                                    <x-admin::form.control-group.error control-name="crs_and_renewals_obv" />
                                </x-admin::form.control-group>

                                <!-- Financial Year -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        {{ trans('sales::app.targets.financial-year') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="financial_year"
                                        v-model="form.financial_year"
                                        :label="trans('sales::app.targets.financial-year')"
                                    >
                                        <option value="">{{ trans('sales::app.common.select') }}</option>
                                        @for ($i = 0; $i < 10; $i++)
                                            <option value="FY{{ date('y') + $i + 1 }}">FY{{ date('y') + $i + 1 }}</option>
                                        @endfor
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="financial_year" />
                                </x-admin::form.control-group>

                                <!-- Quarter -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        {{ trans('sales::app.targets.quarter') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="quarter"
                                        v-model="form.quarter"
                                        :label="trans('sales::app.targets.quarter')"
                                    >
                                        <option value="">{{ trans('sales::app.common.select') }}</option>
                                        <option value="Q1">Q1</option>
                                        <option value="Q2">Q2</option>
                                        <option value="Q3">Q3</option>
                                        <option value="Q4">Q4</option>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="quarter" />
                                </x-admin::form.control-group>

                                <!-- Description -->
                                <x-admin::form.control-group class="col-span-2">
                                    <x-admin::form.control-group.label>
                                        {{ trans('sales::app.targets.description') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="textarea"
                                        name="description"
                                        v-model="form.description"
                                        :label="trans('sales::app.targets.description')"
                                        :placeholder="trans('sales::app.targets.description')"
                                    />

                                    <x-admin::form.control-group.error control-name="description" />
                                </x-admin::form.control-group>
                            </div>
                        </div>

                        <!-- Assignment -->
                        <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                                {{ trans('sales::app.common.assignment') }}
                            </p>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Assignee Type -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.targets.assignee-type') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="assignee_type"
                                        v-model="form.assignee_type"
                                        rules="required"
                                        :label="trans('sales::app.targets.assignee-type')"
                                        @change="resetAssigneeId"
                                    >
                                        <option value="">{{ trans('sales::app.common.select') }}</option>
                                        <option value="individual">{{ trans('sales::app.targets.individual') }}</option>
                                        <option value="team">{{ trans('sales::app.targets.team') }}</option>
                                        <option value="region">{{ trans('sales::app.targets.region') }}</option>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="assignee_type" />
                                </x-admin::form.control-group>

                                <!-- Assignee -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.targets.assignee-id') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="assignee_id"
                                        v-model="form.assignee_id"
                                        rules="required"
                                        :label="trans('sales::app.targets.assignee-id')"
                                    >
                                        <option value="">{{ trans('sales::app.common.select') }}</option>
                                        <template v-if="form.assignee_type === 'individual'">
                                            <option
                                                v-for="individual in assigneeOptions.individuals"
                                                :key="individual.id"
                                                :value="individual.id"
                                            >
                                                @{{ individual.name }}
                                            </option>
                                        </template>
                                        <template v-if="form.assignee_type === 'team'">
                                            <option
                                                v-for="team in assigneeOptions.teams"
                                                :key="team.id"
                                                :value="team.id"
                                            >
                                                @{{ team.name }}
                                            </option>
                                        </template>
                                        <template v-if="form.assignee_type === 'region'">
                                            <option
                                                v-for="region in assigneeOptions.regions"
                                                :key="region.id"
                                                :value="region.id"
                                            >
                                                @{{ region.name }}
                                            </option>
                                        </template>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="assignee_id" />
                                </x-admin::form.control-group>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel -->
                    <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">
                        <!-- Time Period -->
                        <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                                {{ trans('sales::app.common.time-period') }}
                            </p>

                            <div class="grid gap-4">
                                <!-- Period Type -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.common.period-type') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="period_type"
                                        v-model="form.period_type"
                                        rules="required"
                                        :label="trans('sales::app.common.period-type')"
                                    >
                                        <option value="">{{ trans('sales::app.common.select') }}</option>
                                        <option value="daily">{{ trans('sales::app.common.daily') }}</option>
                                        <option value="weekly">{{ trans('sales::app.common.weekly') }}</option>
                                        <option value="monthly">{{ trans('sales::app.common.monthly') }}</option>
                                        <option value="quarterly">{{ trans('sales::app.common.quarterly') }}</option>
                                        <option value="half_yearly">{{ trans('sales::app.common.half-yearly') }}</option>
                                        <option value="annual">{{ trans('sales::app.common.annual') }}</option>
                                        <option value="custom">{{ trans('sales::app.common.custom') }}</option>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="period_type" />
                                </x-admin::form.control-group>

                                <!-- Start Date -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.targets.start-date') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="date"
                                        name="start_date"
                                        v-model="form.start_date"
                                        rules="required"
                                        :label="trans('sales::app.targets.start-date')"
                                    />

                                    <x-admin::form.control-group.error control-name="start_date" />
                                </x-admin::form.control-group>

                                <!-- End Date -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.targets.end-date') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="date"
                                        name="end_date"
                                        v-model="form.end_date"
                                        rules="required|after:start_date"
                                        :label="trans('sales::app.targets.end-date')"
                                    />

                                    <x-admin::form.control-group.error control-name="end_date" />
                                </x-admin::form.control-group>

                                <!-- Status -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.targets.status') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="status"
                                        v-model="form.status"
                                        rules="required"
                                        :label="trans('sales::app.targets.status')"
                                    >
                                        <option value="">{{ trans('admin::app.layouts.select') }}</option>
                                        <option value="active">{{ trans('sales::app.targets.active') }}</option>
                                        <option value="paused">{{ trans('sales::app.targets.paused') }}</option>
                                        <option value="completed">{{ trans('sales::app.targets.completed') }}</option>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="status" />
                                </x-admin::form.control-group>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                                {{ trans('sales::app.targets.notes') }}
                            </p>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.control
                                    type="textarea"
                                    name="notes"
                                    v-model="form.notes"
                                    :label="trans('sales::app.targets.notes')"
                                    :placeholder="trans('sales::app.targets.notes')"
                                />

                                <x-admin::form.control-group.error control-name="notes" />
                            </x-admin::form.control-group>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </script>

    <script type="module">
        app.component('v-sales-target-form', {
            template: '#v-sales-target-form-template',

            props: {
                assigneeOptions: {
                    type: Object,
                    required: true,
                },

                action: {
                    type: String,
                    required: true,
                },

                csrfToken: {
                    type: String,
                    required: true,
                },
            },

            data() {
                return {
                    form: {
                        name: '',
                        description: '',
                        target_amount: '',
                        target_for_new_logo: '',
                        crs_and_renewals_obv: '',
                        financial_year: '',
                        quarter: '',
                        assignee_type: '',
                        assignee_id: '',
                        start_date: '',
                        end_date: '',
                        period_type: '',
                        status: 'active',
                        notes: '',
                    },

                    isLoading: false,
                    errors: {},
                };
            },

            methods: {
                submit() {
                    this.isLoading = true;

                    // Prepare form data with CSRF token
                    const formData = {
                        ...this.form,
                        _token: this.csrfToken
                    };

                    this.$axios.post(this.action, formData)
                        .then(response => {
                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: response.data.message || 'Target created successfully!'
                            });

                            window.location.href = "{{ route('admin.sales.targets.index') }}";
                        })
                        .catch(error => {
                            this.isLoading = false;

                            if (error.response?.status === 422) {
                                // Handle validation errors
                                this.setErrors(error.response.data.errors);
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: 'Please fix the validation errors and try again.'
                                });
                            } else {
                                // Handle other errors
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || 'An error occurred while creating the target.'
                                });
                            }
                        });
                },

                resetAssigneeId() {
                    this.form.assignee_id = '';
                },

                setErrors(errors) {
                    // Clear previous errors
                    this.errors = {};

                    // Set new errors
                    for (const field in errors) {
                        this.errors[field] = errors[field][0]; // Take first error message
                    }
                },
            },
        });
    </script>
@endPushOnce
