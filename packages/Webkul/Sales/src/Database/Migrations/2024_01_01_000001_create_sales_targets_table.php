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
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('target_amount', 15, 2);
            $table->decimal('achieved_amount', 15, 2)->default(0);
            
            // Assignee information
            $table->enum('assignee_type', ['individual', 'team', 'region']);
            $table->unsignedInteger('assignee_id');
            $table->string('assignee_name'); // Denormalized for performance
            
            // Time period
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'half_yearly', 'annual', 'custom']);
            
            // Status and tracking
            $table->enum('status', ['active', 'completed', 'paused', 'cancelled'])->default('active');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            
            // Additional information
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable(); // For storing additional custom fields
            
            // Audit trail
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['assignee_type', 'assignee_id']);
            $table->index(['status', 'start_date', 'end_date']);
            $table->index(['period_type', 'start_date']);
            $table->index('created_by');
            
            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};
