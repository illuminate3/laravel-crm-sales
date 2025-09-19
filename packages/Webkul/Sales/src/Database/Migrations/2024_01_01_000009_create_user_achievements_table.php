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
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('achievement_id');
            
            // Award details
            $table->timestamp('earned_at');
            $table->integer('points_awarded')->default(0);
            $table->json('criteria_met')->nullable(); // Store the specific criteria that were met
            
            // Context information
            $table->unsignedBigInteger('related_target_id')->nullable(); // If related to a specific target
            $table->string('period_type')->nullable(); // daily, weekly, monthly, etc.
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'earned_at']);
            $table->index(['achievement_id', 'earned_at']);
            $table->index(['user_id', 'achievement_id']);
            $table->index('related_target_id');
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('achievement_id')->references('id')->on('sales_achievements')->onDelete('cascade');
            $table->foreign('related_target_id')->references('id')->on('sales_targets')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
