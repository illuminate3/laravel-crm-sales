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
        // Add foreign key relationships and improve sales_performance table
        Schema::table('sales_performance', function (Blueprint $table) {
            // Add relationship to sales_targets
            if (!Schema::hasColumn('sales_performance', 'sales_target_id')) {
                $table->unsignedBigInteger('sales_target_id')->nullable()->after('entity_id');
                $table->foreign('sales_target_id')->references('id')->on('sales_targets')->onDelete('set null');
            }
            
            // Add parent performance tracking for team hierarchies
            if (!Schema::hasColumn('sales_performance', 'parent_performance_id')) {
                $table->unsignedBigInteger('parent_performance_id')->nullable()->after('sales_target_id');
                $table->foreign('parent_performance_id')->references('id')->on('sales_performance')->onDelete('set null');
            }
            
            // Add fields for better tracking
            if (!Schema::hasColumn('sales_performance', 'is_team_aggregate')) {
                $table->boolean('is_team_aggregate')->default(false)->after('entity_name');
            }
            if (!Schema::hasColumn('sales_performance', 'member_contributions')) {
                $table->json('member_contributions')->nullable()->after('metadata'); // Track individual contributions to team performance
            }
            if (!Schema::hasColumn('sales_performance', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('calculated_at');
            }
            
            // Add indexes for performance
            if (!Schema::hasColumn('sales_performance', 'sales_target_id') || !Schema::hasColumn('sales_performance', 'period_start') || !Schema::hasIndex('sales_performance', ['sales_target_id', 'period_start'])) {
                $table->index(['sales_target_id', 'period_start']);
            }
            if (!Schema::hasColumn('sales_performance', 'parent_performance_id') || !Schema::hasIndex('sales_performance', ['parent_performance_id'])) {
                $table->index(['parent_performance_id']);
            }
            if (!Schema::hasColumn('sales_performance', 'is_team_aggregate') || !Schema::hasColumn('sales_performance', 'entity_type') || !Schema::hasIndex('sales_performance', ['is_team_aggregate', 'entity_type'])) {
                $table->index(['is_team_aggregate', 'entity_type']);
            }
        });

        // Add role_name mapping to existing sales_team_members if column doesn't exist
        if (!Schema::hasColumn('sales_team_members', 'role_name')) {
            Schema::table('sales_team_members', function (Blueprint $table) {
                $table->string('role_name')->nullable()->after('role');
                $table->decimal('contribution_percentage', 5, 2)->default(100.00)->after('is_active');
                $table->index(['role_name', 'is_active']);
            });
        }

        // Create a new table to track sales target assignments to teams/individuals
        if (!Schema::hasTable('sales_target_assignments')) {
            Schema::create('sales_target_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_target_id');
            $table->enum('assignee_type', ['individual', 'team', 'region']);
            $table->unsignedInteger('assignee_id');
            $table->string('assignee_name'); // Denormalized for performance
            $table->decimal('allocated_amount', 15, 2); // Portion of target allocated to this assignee
            $table->decimal('achieved_amount', 15, 2)->default(0);
            $table->decimal('allocation_percentage', 5, 2)->default(100.00);
            $table->boolean('is_primary')->default(true); // Primary assignee vs. contributing member
            $table->timestamps();

            // Foreign keys
            $table->foreign('sales_target_id')->references('id')->on('sales_targets')->onDelete('cascade');
            
            // Indexes
            $table->index(['sales_target_id', 'assignee_type', 'assignee_id']);
            $table->index(['assignee_type', 'assignee_id']);
            $table->index(['is_primary', 'assignee_type']);
        });
        }

        // Create table to track lead-to-sales conversion for performance calculation
        if (!Schema::hasTable('sales_conversions')) {
            Schema::create('sales_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('lead_id');
            $table->unsignedInteger('user_id'); // Sales person responsible
            $table->unsignedBigInteger('sales_target_id')->nullable(); // Which target this contributes to
            $table->decimal('conversion_amount', 15, 2);
            $table->date('conversion_date');
            $table->enum('conversion_type', ['new_logo', 'renewal', 'upsell', 'cross_sell'])->default('new_logo');
            $table->boolean('is_counted')->default(true); // For excluding from calculations if needed
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sales_target_id')->references('id')->on('sales_targets')->onDelete('set null');

            // Indexes
            $table->index(['user_id', 'conversion_date']);
            $table->index(['sales_target_id', 'conversion_date']);
            $table->index(['conversion_type', 'conversion_date']);
            $table->index(['is_counted', 'conversion_date']);
            
            // Unique constraint to prevent duplicate conversions
            $table->unique(['lead_id', 'user_id'], 'unique_lead_conversion');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_conversions');
        Schema::dropIfExists('sales_target_assignments');
        
        Schema::table('sales_performance', function (Blueprint $table) {
            $table->dropForeign(['sales_target_id']);
            $table->dropForeign(['parent_performance_id']);
            $table->dropColumn([
                'sales_target_id',
                'parent_performance_id',
                'is_team_aggregate',
                'member_contributions',
                'last_synced_at'
            ]);
        });

        if (Schema::hasColumn('sales_team_members', 'role_name')) {
            Schema::table('sales_team_members', function (Blueprint $table) {
                $table->dropColumn(['role_name', 'contribution_percentage']);
            });
        }
    }
};
