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
        Schema::create('sales_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Report configuration
            $table->enum('type', ['commission', 'yoy_growth', 'pipeline_health', 'custom']);
            $table->json('filters'); // Store report filters and parameters
            $table->json('columns'); // Store selected columns for the report
            $table->json('grouping')->nullable(); // Store grouping configuration
            $table->json('sorting')->nullable(); // Store sorting configuration
            
            // Date range
            $table->date('date_from');
            $table->date('date_to');
            
            // Report data and status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->longText('data')->nullable(); // Store generated report data
            $table->string('file_path')->nullable(); // Store exported file path
            $table->timestamp('generated_at')->nullable();
            $table->text('error_message')->nullable();
            
            // Access control
            $table->unsignedInteger('created_by');
            $table->boolean('is_public')->default(false);
            $table->json('shared_with')->nullable(); // Store user IDs who have access
            
            // Scheduling
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable(); // daily, weekly, monthly
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['type', 'status']);
            $table->index(['created_by', 'is_public']);
            $table->index(['date_from', 'date_to']);
            $table->index(['is_scheduled', 'next_run_at']);
            
            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_reports');
    }
};
