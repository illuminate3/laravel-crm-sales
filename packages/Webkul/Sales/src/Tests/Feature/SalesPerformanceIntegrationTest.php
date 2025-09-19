<?php

namespace Webkul\Sales\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Webkul\Sales\Models\SalesTarget;
use Webkul\Sales\Models\SalesPerformance;
use Webkul\Sales\Models\SalesConversion;
use Webkul\Sales\Models\SalesTeam;
use Webkul\Sales\Models\SalesTeamMember;
use Webkul\Sales\Observers\SalesTargetObserver;
use Webkul\Sales\Observers\SalesPerformanceObserver;
use Webkul\User\Models\User;
use Webkul\User\Models\Role;

class SalesPerformanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;
    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Create roles
        $managerRole = Role::factory()->create(['name' => 'Sales Manager']);
        $repRole = Role::factory()->create(['name' => 'Sales Representative']);
        
        // Create users
        $this->manager = User::factory()->create(['role_id' => $managerRole->id]);
        $this->user = User::factory()->create(['role_id' => $repRole->id]);
        
        // Create team
        $this->team = SalesTeam::factory()->create(['manager_id' => $this->manager->id]);
        
        // Add user to team
        SalesTeamMember::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'role' => 'member',
            'role_name' => $repRole->name,
            'joined_at' => now()->subDays(30),
            'is_active' => true,
            'contribution_percentage' => 100.00,
        ]);
    }

    /** @test */
    public function target_observer_triggers_performance_calculation()
    {
        // Create target
        $target = SalesTarget::factory()->create([
            'assignee_type' => 'individual',
            'assignee_id' => $this->user->id,
            'assignee_name' => $this->user->name,
            'target_amount' => 10000,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'status' => 'active',
        ]);

        // Performance should be created automatically via observer
        $performance = SalesPerformance::where('sales_target_id', $target->id)->first();
        $this->assertNotNull($performance);
    }

    /** @test */
    public function updating_target_amount_triggers_performance_recalculation()
    {
        // Create target and initial performance
        $target = SalesTarget::factory()->create([
            'assignee_type' => 'individual',
            'assignee_id' => $this->user->id,
            'assignee_name' => $this->user->name,
            'target_amount' => 10000,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'status' => 'active',
        ]);

        // Create conversion
        SalesConversion::create([
            'lead_id' => 1,
            'user_id' => $this->user->id,
            'sales_target_id' => $target->id,
            'conversion_amount' => 5000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        // Update target amount
        $target->update(['target_amount' => 20000]);

        // Performance should be recalculated
        $performance = SalesPerformance::where('sales_target_id', $target->id)->first();
        $this->assertEquals(25, $performance->achievement_percentage); // 5000/20000 * 100
    }

    /** @test */
    public function individual_performance_update_triggers_team_recalculation()
    {
        // Create team target
        $teamTarget = SalesTarget::factory()->create([
            'assignee_type' => 'team',
            'assignee_id' => $this->team->id,
            'assignee_name' => $this->team->name,
            'target_amount' => 30000,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'status' => 'active',
        ]);

        // Create individual performance first
        $individualPerformance = SalesPerformance::create([
            'entity_type' => 'individual',
            'entity_id' => $this->user->id,
            'entity_name' => $this->user->name,
            'sales_target_id' => $teamTarget->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'target_amount' => 10000,
            'achieved_amount' => 8000,
            'calculated_at' => now(),
        ]);

        // Update individual performance - should trigger team recalculation
        $individualPerformance->update(['achieved_amount' => 12000]);

        // Team performance should be created/updated automatically
        $teamPerformance = SalesPerformance::where('entity_type', 'team')
            ->where('entity_id', $this->team->id)
            ->where('is_team_aggregate', true)
            ->first();

        $this->assertNotNull($teamPerformance);
        $this->assertEquals(12000, $teamPerformance->achieved_amount);
    }

    /** @test */
    public function performance_api_endpoints_work_correctly()
    {
        // Create target and performance data
        $target = SalesTarget::factory()->create([
            'assignee_type' => 'individual',
            'assignee_id' => $this->user->id,
            'assignee_name' => $this->user->name,
            'target_amount' => 15000,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'status' => 'active',
        ]);

        SalesConversion::create([
            'lead_id' => 1,
            'user_id' => $this->user->id,
            'sales_target_id' => $target->id,
            'conversion_amount' => 9000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        // Test performance dashboard
        $response = $this->get('/admin/sales/performance');
        $response->assertStatus(200);

        // Test performance stats API
        $response = $this->get('/admin/sales/performance/stats?type=overview');
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_records',
                    'total_target_amount',
                    'total_achieved_amount',
                    'overall_achievement',
                ]);

        // Test target vs actual API
        $response = $this->get('/admin/sales/performance/stats?type=target-vs-actual&view_type=individual');
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'chart_data',
                ]);

        // Test view switching
        $response = $this->post('/admin/sales/performance/switch-view', [
            'view_type' => 'team',
            'period' => 'monthly',
        ]);
        $response->assertStatus(200)
                ->assertJson(['success' => true]);
    }

    /** @test */
    public function team_hierarchy_maintains_consistency()
    {
        // Create multiple team members
        $user2 = User::factory()->create(['role_id' => Role::first()->id]);
        $user3 = User::factory()->create(['role_id' => Role::first()->id]);

        SalesTeamMember::create([
            'team_id' => $this->team->id,
            'user_id' => $user2->id,
            'role' => 'member',
            'role_name' => 'Sales Representative',
            'joined_at' => now()->subDays(20),
            'is_active' => true,
            'contribution_percentage' => 80.00,
        ]);

        SalesTeamMember::create([
            'team_id' => $this->team->id,
            'user_id' => $user3->id,
            'role' => 'lead',
            'role_name' => 'Sales Representative',
            'joined_at' => now()->subDays(10),
            'is_active' => true,
            'contribution_percentage' => 100.00,
        ]);

        // Create team target
        $teamTarget = SalesTarget::factory()->create([
            'assignee_type' => 'team',
            'assignee_id' => $this->team->id,
            'assignee_name' => $this->team->name,
            'target_amount' => 50000,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'status' => 'active',
        ]);

        // Create conversions for each member
        SalesConversion::create([
            'lead_id' => 1,
            'user_id' => $this->user->id,
            'sales_target_id' => $teamTarget->id,
            'conversion_amount' => 10000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        SalesConversion::create([
            'lead_id' => 2,
            'user_id' => $user2->id,
            'sales_target_id' => $teamTarget->id,
            'conversion_amount' => 8000,
            'conversion_date' => now(),
            'conversion_type' => 'renewal',
            'is_counted' => true,
        ]);

        SalesConversion::create([
            'lead_id' => 3,
            'user_id' => $user3->id,
            'sales_target_id' => $teamTarget->id,
            'conversion_amount' => 12000,
            'conversion_date' => now(),
            'conversion_type' => 'upsell',
            'is_counted' => true,
        ]);

        // Calculate team performance
        app(\Webkul\Sales\Services\SalesPerformanceCalculationService::class)
            ->calculatePerformanceForTarget($teamTarget);

        // Verify team performance
        $teamPerformance = SalesPerformance::where('entity_type', 'team')
            ->where('entity_id', $this->team->id)
            ->first();

        // Expected: 10000 (100%) + 6400 (80% of 8000) + 12000 (100%) = 28400
        $expectedTeamTotal = 10000 + (8000 * 0.8) + 12000;
        $this->assertEquals($expectedTeamTotal, $teamPerformance->achieved_amount);

        // Verify individual performances are linked
        $individualPerformances = SalesPerformance::where('parent_performance_id', $teamPerformance->id)->get();
        $this->assertCount(3, $individualPerformances);

        // Verify member contributions are tracked
        $contributions = $teamPerformance->member_contributions;
        $this->assertCount(3, $contributions);
        
        // Find user2's contribution (should be 80%)
        $user2Contribution = collect($contributions)->firstWhere('user_id', $user2->id);
        $this->assertEquals(8000, $user2Contribution['achieved_amount']);
        $this->assertEquals(6400, $user2Contribution['contributed_amount']);
        $this->assertEquals(80, $user2Contribution['contribution_percentage']);
    }

    /** @test */
    public function data_consistency_is_maintained_across_updates()
    {
        // Create target
        $target = SalesTarget::factory()->create([
            'assignee_type' => 'individual',
            'assignee_id' => $this->user->id,
            'assignee_name' => $this->user->name,
            'target_amount' => 20000,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'status' => 'active',
        ]);

        // Create initial conversion
        $conversion1 = SalesConversion::create([
            'lead_id' => 1,
            'user_id' => $this->user->id,
            'sales_target_id' => $target->id,
            'conversion_amount' => 5000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        // Verify initial state
        $target->refresh();
        $this->assertEquals(5000, $target->achieved_amount);

        // Add another conversion
        $conversion2 = SalesConversion::create([
            'lead_id' => 2,
            'user_id' => $this->user->id,
            'sales_target_id' => $target->id,
            'conversion_amount' => 7500,
            'conversion_date' => now(),
            'conversion_type' => 'renewal',
            'is_counted' => true,
        ]);

        // Update target achieved amount
        $target->updateAchievedAmount();
        $this->assertEquals(12500, $target->achieved_amount);

        // Disable one conversion
        $conversion1->update(['is_counted' => false]);
        $target->updateAchievedAmount();
        $this->assertEquals(7500, $target->achieved_amount);

        // Re-enable conversion
        $conversion1->update(['is_counted' => true]);
        $target->updateAchievedAmount();
        $this->assertEquals(12500, $target->achieved_amount);

        // Verify performance record is consistent
        $performance = SalesPerformance::where('sales_target_id', $target->id)->first();
        $this->assertEquals(12500, $performance->achieved_amount);
        $this->assertEquals(62.5, $performance->achievement_percentage);
    }
}
