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
        Schema::create('sales_target_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_target_id');
            
            // Adjustment details
            $table->enum('adjustment_type', ['amount', 'date', 'assignee', 'status']);
            $table->json('old_value');
            $table->json('new_value');
            $table->text('reason');
            
            // Audit information
            $table->unsignedInteger('adjusted_by');
            $table->timestamp('adjusted_at');
            $table->timestamps();
            
            // Indexes
            $table->index('sales_target_id');
            $table->index(['adjustment_type', 'adjusted_at']);
            $table->index('adjusted_by');
            
            // Foreign keys
            $table->foreign('sales_target_id')->references('id')->on('sales_targets')->onDelete('cascade');
            $table->foreign('adjusted_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_target_adjustments');
    }
};
