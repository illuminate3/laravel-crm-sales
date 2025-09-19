<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_targets', function (Blueprint $table) {
            $table->integer('target_for_new_logo')->after('target_amount')->nullable();
            $table->decimal('crs_and_renewals_obv', 15, 2)->after('target_for_new_logo')->nullable();
            $table->string('financial_year')->after('crs_and_renewals_obv')->nullable();
            $table->string('quarter')->after('financial_year')->nullable();
            $table->integer('achieved_new_logos')->after('achieved_amount')->nullable();
            $table->decimal('achieved_crs_and_renewals_obv', 15, 2)->after('achieved_new_logos')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_targets', function (Blueprint $table) {
            $table->dropColumn('target_for_new_logo');
            $table->dropColumn('crs_and_renewals_obv');
            $table->dropColumn('financial_year');
            $table->dropColumn('quarter');
            $table->dropColumn('achieved_new_logos');
            $table->dropColumn('achieved_crs_and_renewals_obv');
        });
    }
};
