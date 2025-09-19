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
        Schema::table('sales_performance', function (Blueprint $table) {
            $table->decimal('average_deal_size', 15, 2)->default(0)->after('conversion_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_performance', function (Blueprint $table) {
            $table->dropColumn('average_deal_size');
        });
    }
};
