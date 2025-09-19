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
        Schema::create('sales_achievements', function (Blueprint $table) {
            $table->id();
            
            // Achievement details
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('badge_icon')->nullable();
            $table->string('badge_color', 7)->default('#3B82F6'); // Hex color code
            
            // Achievement criteria
            $table->enum('type', ['target_achievement', 'streak', 'milestone', 'performance', 'custom']);
            $table->json('criteria'); // Store achievement criteria (e.g., {"target_percentage": 100, "period": "monthly"})
            $table->integer('points')->default(0); // Points awarded for this achievement
            
            // Achievement status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_repeatable')->default(false); // Can be earned multiple times
            $table->integer('max_awards')->nullable(); // Maximum times this can be awarded (null = unlimited)
            
            // Metadata
            $table->json('metadata')->nullable(); // Additional configuration
            $table->timestamps();
            
            // Indexes
            $table->index(['type', 'is_active']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_achievements');
    }
};
