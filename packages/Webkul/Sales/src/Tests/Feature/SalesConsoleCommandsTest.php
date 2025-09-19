<?php

namespace Webkul\Sales\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Webkul\Sales\Models\SalesTarget;
use Webkul\Sales\Models\SalesPerformance;
use Webkul\Sales\Models\SalesConversion;
use Webkul\Sales\Models\SalesTeam;
use Webkul\Sales\Models\SalesTeamMember;
use Webkul\User\Models\User;
use Webkul\User\Models\Role;
use Webkul\Lead\Models\Lead;

class SalesConsoleCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        $role = Role::factory()->create(['name' => 'Sales Representative']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
        
        $this->target = SalesTarget::factory()->create([
            'assignee_type' => 'individual',
            'assignee_id' => $this->user->id,
            'assignee_name' => $this->user->name,
            'target_amount' => 10000,
            'achieved_amount' => 0,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function recalculate_performance_command_works()
    {
        // Create some conversions
        SalesConversion::create([
            'lead_id' => 1,
            'user_id' => $this->user->id,
            'sales_target_id' => $this->target->id,
            'conversion_amount' => 5000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        // Run recalculation command
        $this->artisan('sales:recalculate-performance')
            ->expectsOutput('Starting sales performance recalculation...')
            ->expectsOutput('Sales performance recalculation completed successfully!')
            ->assertExitCode(0);

        // Verify performance was calculated
        $performance = SalesPerformance::where('sales_target_id', $this->target->id)->first();
        $this->assertNotNull($performance);
        $this->assertEquals(5000, $performance->achieved_amount);

        // Verify target was updated
        $this->target->refresh();
        $this->assertEquals(5000, $this->target->achieved_amount);
    }

    /** @test */
    public function recalculate_specific_target_works()
    {
        // Create conversion
        SalesConversion::create([
            'lead_id' => 1,
            'user_id' => $this->user->id,
            'sales_target_id' => $this->target->id,
            'conversion_amount' => 3000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        // Run command for specific target
        $this->artisan('sales:recalculate-performance', ['--target-id' => $this->target->id])
            ->expectsOutput('Starting sales performance recalculation...')
            ->assertExitCode(0);

        // Verify only this target was processed
        $performance = SalesPerformance::where('sales_target_id', $this->target->id)->first();
        $this->assertNotNull($performance);
        $this->assertEquals(3000, $performance->achieved_amount);
    }

    /** @test */
    public function clean_option_removes_existing_data()
    {
        // Create existing performance data
        SalesPerformance::create([
            'entity_type' => 'individual',
            'entity_id' => $this->user->id,
            'entity_name' => $this->user->name,
            'sales_target_id' => $this->target->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'target_amount' => 10000,
            'achieved_amount' => 8000,
            'calculated_at' => now(),
        ]);

        $this->assertCount(1, SalesPerformance::all());

        // Run command with clean option
        $this->artisan('sales:recalculate-performance', ['--clean'])
            ->expectsOutput('Cleaning existing performance data...')
            ->expectsOutput('Cleared sales performance records')
            ->assertExitCode(0);

        // Verify data was cleaned
        $this->assertCount(0, SalesPerformance::all());
    }

    /** @test */
    public function migrate_data_command_works()
    {
        // Create team member without role name
        $team = SalesTeam::factory()->create(['manager_id' => $this->user->id]);
        $teamMember = SalesTeamMember::create([
            'team_id' => $team->id,
            'user_id' => $this->user->id,
            'role' => 'member',
            'role_name' => null, // Missing role name
            'joined_at' => now()->subDays(30),
            'is_active' => true,
            'contribution_percentage' => 100.00,
        ]);

        // Run migration command
        $this->artisan('sales:migrate-data', ['--fix-team-roles'])
            ->expectsOutput('Starting sales data migration...')
            ->expectsOutput('Fixing team member role names...')
            ->expectsOutput('Sales data migration completed successfully!')
            ->assertExitCode(0);

        // Verify role name was fixed
        $teamMember->refresh();
        $this->assertNotNull($teamMember->role_name);
        $this->assertEquals($this->user->role->name, $teamMember->role_name);
    }

    /** @test */
    public function migrate_data_dry_run_works()
    {
        // Create team member without role name
        $team = SalesTeam::factory()->create(['manager_id' => $this->user->id]);
        $teamMember = SalesTeamMember::create([
            'team_id' => $team->id,
            'user_id' => $this->user->id,
            'role' => 'member',
            'role_name' => null,
            'joined_at' => now()->subDays(30),
            'is_active' => true,
            'contribution_percentage' => 100.00,
        ]);

        // Run dry run
        $this->artisan('sales:migrate-data', ['--fix-team-roles', '--dry-run'])
            ->expectsOutput('DRY RUN MODE - No changes will be made')
            ->expectsOutput('Dry run completed - no changes were made')
            ->assertExitCode(0);

        // Verify no changes were made
        $teamMember->refresh();
        $this->assertNull($teamMember->role_name);
    }

    /** @test */
    public function validate_data_command_finds_issues()
    {
        // Create data with issues
        
        // 1. Target with negative achieved amount
        $badTarget = SalesTarget::factory()->create([
            'assignee_type' => 'individual',
            'assignee_id' => $this->user->id,
            'assignee_name' => $this->user->name,
            'target_amount' => 10000,
            'achieved_amount' => -1000, // Negative amount
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'status' => 'active',
        ]);

        // 2. Duplicate performance records
        SalesPerformance::create([
            'entity_type' => 'individual',
            'entity_id' => $this->user->id,
            'entity_name' => $this->user->name,
            'sales_target_id' => $this->target->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'target_amount' => 10000,
            'achieved_amount' => 5000,
            'calculated_at' => now(),
        ]);

        SalesPerformance::create([
            'entity_type' => 'individual',
            'entity_id' => $this->user->id,
            'entity_name' => $this->user->name,
            'sales_target_id' => $this->target->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'target_amount' => 10000,
            'achieved_amount' => 6000,
            'calculated_at' => now(),
        ]);

        // Run validation
        $this->artisan('sales:validate-data')
            ->expectsOutput('Validating sales data integrity...')
            ->expectsOutputToContain('Found 1 targets with negative achieved amounts')
            ->expectsOutputToContain('Found 1 duplicate performance record groups')
            ->assertExitCode(2); // Should return number of issues found
    }

    /** @test */
    public function validate_data_command_fixes_issues()
    {
        // Create target with negative achieved amount
        $badTarget = SalesTarget::factory()->create([
            'assignee_type' => 'individual',
            'assignee_id' => $this->user->id,
            'assignee_name' => $this->user->name,
            'target_amount' => 10000,
            'achieved_amount' => -500,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'status' => 'active',
        ]);

        // Run validation with fix option
        $this->artisan('sales:validate-data', ['--fix'])
            ->expectsOutput('Attempting to fix 1 fixable issues...')
            ->expectsOutput('Fixing: Found 1 targets with negative achieved amounts')
            ->expectsOutput('✅ Reset negative achieved amounts to zero')
            ->assertExitCode(0); // Should be 0 after fixing

        // Verify fix was applied
        $badTarget->refresh();
        $this->assertEquals(0, $badTarget->achieved_amount);
    }

    /** @test */
    public function validate_data_passes_with_clean_data()
    {
        // Create clean data
        SalesConversion::create([
            'lead_id' => 1,
            'user_id' => $this->user->id,
            'sales_target_id' => $this->target->id,
            'conversion_amount' => 5000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        SalesPerformance::create([
            'entity_type' => 'individual',
            'entity_id' => $this->user->id,
            'entity_name' => $this->user->name,
            'sales_target_id' => $this->target->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'target_amount' => 10000,
            'achieved_amount' => 5000,
            'calculated_at' => now(),
        ]);

        // Run validation
        $this->artisan('sales:validate-data')
            ->expectsOutput('✅ All sales data validation checks passed!')
            ->assertExitCode(0);
    }

    /** @test */
    public function create_conversions_option_works()
    {
        // This test would require mocking the Lead model since it's not fully set up
        // For now, we'll test that the command runs without error
        
        $this->artisan('sales:migrate-data', ['--create-conversions', '--dry-run'])
            ->expectsOutput('DRY RUN MODE - No changes will be made')
            ->expectsOutput('Creating conversion records from won leads...')
            ->assertExitCode(0);
    }
}
