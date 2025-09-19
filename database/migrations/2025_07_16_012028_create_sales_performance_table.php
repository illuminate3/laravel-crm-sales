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
        Schema::create('sales_performance', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('period_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_type');
            $table->string('entity_name');
            $table->decimal('target_amount', 12, 2)->default(0);
            $table->decimal('achieved_amount', 12, 2)->default(0);
            $table->decimal('achievement_percentage', 5, 2)->default(0);
            $table->integer('leads_count')->default(0);
            $table->integer('won_leads_count')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('score', 12, 2)->default(0);
            $table->integer('rank')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_performance');
    }
};
