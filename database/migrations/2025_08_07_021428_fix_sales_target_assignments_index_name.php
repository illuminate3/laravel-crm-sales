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
        Schema::table('sales_target_assignments', function (Blueprint $table) {
            // Drop the existing index with the long name
            if (Schema::hasIndex('sales_target_assignments', ['sales_target_id', 'assignee_type', 'assignee_id'])) {
                $table->dropIndex('sales_target_assignments_sales_target_id_assignee_type_assignee_id_index');
            }

            // Add a new index with a shorter name
            $table->index(['sales_target_id', 'assignee_type', 'assignee_id'], 'sales_target_assigns_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_target_assignments', function (Blueprint $table) {
            // Revert the changes in the down method
            $table->dropIndex('sales_target_assigns_idx');
            $table->index(['sales_target_id', 'assignee_type', 'assignee_id']);
        });
    }
};
