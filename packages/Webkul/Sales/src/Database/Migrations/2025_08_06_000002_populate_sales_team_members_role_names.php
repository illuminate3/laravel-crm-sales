<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update role_name in sales_team_members based on user's role
        DB::statement("
            UPDATE sales_team_members stm
            JOIN users u ON stm.user_id = u.id
            JOIN roles r ON u.role_id = r.id
            SET stm.role_name = r.name
            WHERE stm.role_name IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset role_name to null
        DB::table('sales_team_members')->update(['role_name' => null]);
    }
};
