<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Sales\Models\SalesTeam;
use Webkul\Sales\Models\SalesTeamMember;
use Webkul\User\Models\User;

class SalesTeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $firstUser = User::first();

        if (! $firstUser) {
            $this->command->error('No users found. Please seed users first.');
            return;
        }

        // Create a sample sales team
        $salesTeam = SalesTeam::firstOrCreate(
            ['name' => 'Default Sales Team'],
            [
                'description' => 'The primary sales team',
                'manager_id' => $firstUser->id,
            ]
        );

        // Assign existing users to the sales team
        $users = User::all();

        foreach ($users as $user) {
            SalesTeamMember::firstOrCreate(
                [
                    'team_id' => $salesTeam->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => 'member',
                    'role_name' => $user->role->name ?? 'Sales Person',
                    'joined_at' => now(),
                    'is_active' => true,
                    'contribution_percentage' => 100.00,
                ]
            );
        }

        $this->command->info('Sales Team and Members seeded successfully!');
    }
}