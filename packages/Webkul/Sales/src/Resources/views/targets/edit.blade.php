@extends('sales::layouts.master')

@section('title')
    {{ trans('sales::app.targets.edit-title') }}
@stop

@section('content')
    <v-sales-target-form
        :assignee-options="{{ json_encode($assigneeOptions) }}"
        :target="{{ json_encode($target) }}"
        :action="'{{ route('admin.sales.targets.update', $target->id) }}'"
        method="PUT"
    ></v-sales-target-form>
@stop

@pushOnce('scripts')
    <script type="text/x-template" id="v-sales-target-form-template">
        <div class="flex flex-col gap-4">
            <!-- Page Header -->
            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col gap-1">
                        <p class="text-xl font-bold leading-6 text-gray-800 dark:text-white">
                            {{ trans('sales::app.targets.edit-title') }}
                        </p>

                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Update sales target information and settings
                        </p>
                    </div>

                    <div class="flex items-center gap-x-2.5">
                        <a
                            href="{{ route('admin.sales.targets.index') }}"
                            class="transparent-button"
                        >
                            {{ trans('admin::app.layouts.back') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Target Form -->
            <form
                @submit.prevent="onSubmit"
                enctype="multipart/form-data"
            >
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <div class="flex flex-col gap-4">
                    <!-- Basic Information -->
                    <div class="bg-white dark:bg-gray-900 rounded-lg box-shadow">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                {{ trans('sales::app.targets.basic-info') }}
                            </p>
                        </div>

                        <div class="p-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <!-- Target Name -->
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
                                        :label="trans('sales::app.targets.target-amount')"
                                        step="0.01"
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
                            </div>

                            <!-- Achieved Amount -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mt-4">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        {{ trans('sales::app.targets.achieved-amount') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="number"
                                        name="achieved_amount"
                                        v-model="form.achieved_amount"
                                        rules="min_value:0"
                                        :label="trans('sales::app.targets.achieved-amount')"
                                        step="0.01"
                                    />

                                    <x-admin::form.control-group.error control-name="achieved_amount" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        {{ trans('sales::app.targets.achieved-new-logos') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="number"
                                        name="achieved_new_logos"
                                        v-model="form.achieved_new_logos"
                                        rules="integer"
                                        :label="trans('sales::app.targets.achieved-new-logos')"
                                        :placeholder="trans('sales::app.targets.achieved-new-logos')"
                                    />

                                    <x-admin::form.control-group.error control-name="achieved_new_logos" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        {{ trans('sales::app.targets.achieved-crs-and-renewals-obv') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="number"
                                        name="achieved_crs_and_renewals_obv"
                                        v-model="form.achieved_crs_and_renewals_obv"
                                        rules="decimal"
                                        step="0.01"
                                        :label="trans('sales::app.targets.achieved-crs-and-renewals-obv')"
                                        :placeholder="trans('sales::app.targets.achieved-crs-and-renewals-obv')"
                                    />

                                    <x-admin::form.control-group.error control-name="achieved_crs_and_renewals_obv" />
                                </x-admin::form.control-group>
                            </div>

                            <!-- Description -->
                            <div class="mt-4">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        {{ trans('sales::app.targets.description') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="textarea"
                                        name="description"
                                        v-model="form.description"
                                        :label="trans('sales::app.targets.description')"
                                        :placeholder="trans('sales::app.targets.description')"
                                        rows="3"
                                    />

                                    <x-admin::form.control-group.error control-name="description" />
                                </x-admin::form.control-group>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment & Timeline -->
                    <div class="bg-white dark:bg-gray-900 rounded-lg box-shadow">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                Assignment & Timeline
                            </p>
                        </div>

                        <div class="p-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                                        <option value="">Select Type</option>
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
                                        <option value="">Select Assignee</option>
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

                            <!-- Date Range -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mt-4">
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

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        {{ trans('sales::app.targets.end-date') }}
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="date"
                                        name="end_date"
                                        v-model="form.end_date"
                                        rules="required"
                                        :label="trans('sales::app.targets.end-date')"
                                    />

                                    <x-admin::form.control-group.error control-name="end_date" />
                                </x-admin::form.control-group>
                            </div>

                            <!-- Period Type and Status -->
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mt-4">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        Period Type
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="period_type"
                                        v-model="form.period_type"
                                        rules="required"
                                        label="Period Type"
                                    >
                                        <option value="">Select Period</option>
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="half_yearly">Half Yearly</option>
                                        <option value="annual">Annual</option>
                                        <option value="custom">Custom</option>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="period_type" />
                                </x-admin::form.control-group>

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
                                        <option value="active">{{ trans('sales::app.targets.active') }}</option>
                                        <option value="completed">{{ trans('sales::app.targets.completed') }}</option>
                                        <option value="paused">{{ trans('sales::app.targets.paused') }}</option>
                                        <option value="cancelled">Cancelled</option>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="status" />
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
                            {{ trans('sales::app.targets.update-btn-title') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </script>

    <script type="module">
        app.component('v-sales-target-form', {
            template: '#v-sales-target-form-template',

            props: {
                assigneeOptions: {
                    type: Object,
                    required: true,
                },

                target: {
                    type: Object,
                    default: () => ({}),
                },

                action: {
                    type: String,
                    required: true,
                },

                method: {
                    type: String,
                    default: 'POST',
                },
            },

            data() {
                return {
                    form: {
                        name: this.target?.name || '',
                        description: this.target?.description || '',
                        target_amount: this.target?.target_amount || '',
                        target_for_new_logo: this.target?.target_for_new_logo || '',
                        crs_and_renewals_obv: this.target?.crs_and_renewals_obv || '',
                        financial_year: this.target?.financial_year || '',
                        quarter: this.target?.quarter || '',
                        achieved_amount: this.target?.achieved_amount || '',
                        achieved_new_logos: this.target?.achieved_new_logos || '',
                        achieved_crs_and_renewals_obv: this.target?.achieved_crs_and_renewals_obv || '',
                        assignee_type: this.target?.assignee_type || '',
                        assignee_id: this.target?.assignee_id || '',
                        start_date: this.target?.start_date || '',
                        end_date: this.target?.end_date || '',
                        period_type: this.target?.period_type || '',
                        status: this.target?.status || 'active',
                        notes: this.target?.notes || '',
                    },

                    isLoading: false,
                };
            },

            methods: {
                onSubmit(e) {
                    this.isLoading = true;

                    this.$axios.post(this.action, {
                        ...this.form,
                        _method: 'PUT',
                    })
                    .then(response => {
                        this.isLoading = false;
                        
                        window.location.href = "{{ route('admin.sales.targets.index') }}";
                    })
                    .catch(error => {
                        this.isLoading = false;

                        if (error.response.data.errors) {
                            // Handle validation errors
                        }
                    });
                },

                resetAssigneeId() {
                    this.form.assignee_id = '';
                },
            },
        });
    </script>
@endPushOnce
