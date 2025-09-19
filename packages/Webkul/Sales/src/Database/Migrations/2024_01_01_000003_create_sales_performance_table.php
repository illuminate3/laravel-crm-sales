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
            
            // Performance tracking
            $table->enum('entity_type', ['individual', 'team', 'region']);
            $table->unsignedInteger('entity_id');
            $table->string('entity_name'); // Denormalized for performance
            
            // Time period
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'half_yearly', 'annual']);
            
            // Performance metrics
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->decimal('achieved_amount', 15, 2)->default(0);
            $table->decimal('achievement_percentage', 5, 2)->default(0);
            
            // Additional metrics
            $table->integer('leads_count')->default(0);
            $table->integer('won_leads_count')->default(0);
            $table->integer('lost_leads_count')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('average_deal_size', 15, 2)->default(0);
            
            // Gamification
            $table->integer('score')->default(0);
            $table->integer('rank')->nullable();
            $table->json('badges')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            // Indexes
            $table->index(['entity_type', 'entity_id', 'period_start']);
            $table->index(['period_type', 'period_start', 'period_end']);
            $table->index(['achievement_percentage', 'period_start']);
            $table->index(['rank', 'period_start']);
            
            // Unique constraint to prevent duplicate records
            $table->unique(['entity_type', 'entity_id', 'period_start', 'period_end', 'period_type'], 'unique_performance_record');
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
