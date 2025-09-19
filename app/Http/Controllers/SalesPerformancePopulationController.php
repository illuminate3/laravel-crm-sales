<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesPerformancePopulationController extends Controller
{
    public function populate()
    {
        DB::table('sales_performance')->truncate();

        $targets = DB::table('sales_targets')->get();

        foreach ($targets as $target) {
            $wonLeads = DB::table('leads')
                ->where('user_id', $target->assignee_id)
                ->where('stage_id', 'won')
                ->whereBetween('created_at', [$target->start_date, $target->end_date])
                ->count();

            $totalLeads = DB::table('leads')
                ->where('user_id', $target->assignee_id)
                ->whereBetween('created_at', [$target->start_date, $target->end_date])
                ->count();

            DB::table('sales_performance')->insert([
                'period_start'           => $target->start_date,
                'period_end'             => $target->end_date,
                'period_type'            => $target->interval,
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

        return 'Sales performance data populated successfully.';
    }
}
