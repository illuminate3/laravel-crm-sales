<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateSalesPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:populate-performance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the sales_performance table with historical data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Populating sales performance data...');

        DB::table('sales_performance')->truncate();

        $targets = DB::table('sales_targets')->get();

        foreach ($targets as $target) {
            $wonLeads = DB::table('leads')
                ->where('user_id', $target->assignee_id)
                ->whereIn('lead_pipeline_stage_id', function ($query) {
                    $query->select('id')
                        ->from('lead_pipeline_stages')
                        ->where('code', 'won');
                })
                ->whereBetween('created_at', [$target->start_date, $target->end_date])
                ->count();

            $totalLeads = DB::table('leads')
                ->where('user_id', $target->assignee_id)
                ->whereBetween('created_at', [$target->start_date, $target->end_date])
                ->count();

            DB::table('sales_performance')->insert([
                'period_start'           => $target->start_date,
                'period_end'             => $target->end_date,
                'period_type'            => $target->period_type,
                'entity_id'              => $target->assignee_id,
                'entity_type'            => 'individual',
                'entity_name'            => $target->assignee_name,
                'target_amount'          => $target->target_amount,
                'achieved_amount'        => $target->achieved_amount,
                'achievement_percentage' => $target->progress_percentage,
                'leads_count'            => $totalLeads,
                'won_leads_count'        => $wonLeads,
                'conversion_rate'        => $totalLeads > 0 ? ($wonLeads / $totalLeads) * 100 : 0,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }

        $this->info('Sales performance data populated successfully.');
    }
}
