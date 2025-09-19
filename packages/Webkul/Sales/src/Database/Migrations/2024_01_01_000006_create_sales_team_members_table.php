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
        Schema::create('sales_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedInteger('user_id');
            $table->enum('role', ['member', 'lead'])->default('member');
            $table->string('role_name')->nullable(); // Maps to user's role name for easier querying
            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('contribution_percentage', 5, 2)->default(100.00); // For partial team contribution
            $table->timestamps();

            // Indexes
            $table->index(['team_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
            $table->index(['role_name', 'is_active']);

            // Unique constraint
            $table->unique(['team_id', 'user_id'], 'unique_team_member');

            // Foreign keys
            $table->foreign('team_id')->references('id')->on('sales_teams')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_team_members');
    }
};
