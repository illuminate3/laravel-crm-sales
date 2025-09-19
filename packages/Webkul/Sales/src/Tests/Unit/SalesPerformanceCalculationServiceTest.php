<?php

namespace Webkul\Sales\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Webkul\Sales\Services\SalesPerformanceCalculationService;
use Webkul\Sales\Models\SalesTarget;
use Webkul\Sales\Models\SalesPerformance;
use Webkul\Sales\Models\SalesConversion;
use Webkul\Sales\Models\SalesTeam;
use Webkul\Sales\Models\SalesTeamMember;
use Webkul\User\Models\User;
use Webkul\User\Models\Role;
use Webkul\Lead\Models\Lead;
use Carbon\Carbon;

class SalesPerformanceCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $performanceService;
    protected $user;
    protected $team;
    protected $target;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->performanceService = app(SalesPerformanceCalculationService::class);
        
        // Create test data
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Create role
        $role = Role::factory()->create(['name' => 'Sales Representative']);
        
        // Create user
        $this->user = User::factory()->create(['role_id' => $role->id]);
        
        // Create team
        $this->team = SalesTeam::factory()->create(['manager_id' => $this->user->id]);
        
        // Create team member
        SalesTeamMember::create([
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'role' => 'member',
            'role_name' => $role->name,
            'joined_at' => now()->subDays(30),
            'is_active' => true,
            'contribution_percentage' => 100.00,
        ]);
        
        // Create individual target
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
    public function it_calculates_individual_performance_correctly()
    {
        // Create a conversion
        $conversion = SalesConversion::create([
            'lead_id' => 1, // Mock lead ID
            'user_id' => $this->user->id,
            'sales_target_id' => $this->target->id,
            'conversion_amount' => 5000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        // Calculate performance
        $this->performanceService->calculatePerformanceForTarget($this->target);

        // Assert performance was created
        $performance = SalesPerformance::where('entity_type', 'individual')
            ->where('entity_id', $this->user->id)
            ->where('sales_target_id', $this->target->id)
            ->first();

        $this->assertNotNull($performance);
        $this->assertEquals(5000, $performance->achieved_amount);
        $this->assertEquals(50, $performance->achievement_percentage);
        $this->assertFalse($performance->is_team_aggregate);
    }

    /** @test */
    public function it_calculates_team_performance_correctly()
    {
        // Create team target
        $teamTarget = SalesTarget::factory()->create([
            'assignee_type' => 'team',
            'assignee_id' => $this->team->id,
            'assignee_name' => $this->team->name,
            'target_amount' => 20000,
            'achieved_amount' => 0,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'status' => 'active',
        ]);

        // Create conversion for team member
        SalesConversion::create([
            'lead_id' => 1,
            'user_id' => $this->user->id,
            'sales_target_id' => $teamTarget->id,
            'conversion_amount' => 8000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        // Calculate team performance
        $this->performanceService->calculatePerformanceForTarget($teamTarget);

        // Assert team performance was created
        $teamPerformance = SalesPerformance::where('entity_type', 'team')
            ->where('entity_id', $this->team->id)
            ->where('sales_target_id', $teamTarget->id)
            ->first();

        $this->assertNotNull($teamPerformance);
        $this->assertEquals(8000, $teamPerformance->achieved_amount);
        $this->assertEquals(40, $teamPerformance->achievement_percentage);
        $this->assertTrue($teamPerformance->is_team_aggregate);
        $this->assertNotEmpty($teamPerformance->member_contributions);

        // Assert individual performance was also created and linked
        $individualPerformance = SalesPerformance::where('entity_type', 'individual')
            ->where('entity_id', $this->user->id)
            ->where('parent_performance_id', $teamPerformance->id)
            ->first();

        $this->assertNotNull($individualPerformance);
        $this->assertEquals($teamPerformance->id, $individualPerformance->parent_performance_id);
    }

    /** @test */
    public function it_handles_team_member_contribution_percentages()
    {
        // Update team member to have 50% contribution
        $teamMember = SalesTeamMember::where('team_id', $this->team->id)
            ->where('user_id', $this->user->id)
            ->first();
        $teamMember->update(['contribution_percentage' => 50.00]);

        // Create team target
        $teamTarget = SalesTarget::factory()->create([
            'assignee_type' => 'team',
            'assignee_id' => $this->team->id,
            'assignee_name' => $this->team->name,
            'target_amount' => 20000,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'status' => 'active',
        ]);

        // Create conversion
        SalesConversion::create([
            'lead_id' => 1,
            'user_id' => $this->user->id,
            'sales_target_id' => $teamTarget->id,
            'conversion_amount' => 10000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        // Calculate team performance
        $this->performanceService->calculatePerformanceForTarget($teamTarget);

        // Assert team performance reflects 50% contribution
        $teamPerformance = SalesPerformance::where('entity_type', 'team')
            ->where('entity_id', $this->team->id)
            ->first();

        $this->assertEquals(5000, $teamPerformance->achieved_amount); // 50% of 10000
        
        // Check member contributions
        $contributions = $teamPerformance->member_contributions;
        $this->assertCount(1, $contributions);
        $this->assertEquals(10000, $contributions[0]['achieved_amount']);
        $this->assertEquals(5000, $contributions[0]['contributed_amount']);
        $this->assertEquals(50, $contributions[0]['contribution_percentage']);
    }

    /** @test */
    public function it_creates_conversion_from_lead()
    {
        // Mock lead (since we don't have the full Lead model setup)
        $leadData = (object) [
            'id' => 1,
            'user_id' => $this->user->id,
            'lead_value' => 7500,
            'status' => 1, // Won
            'closed_at' => now(),
        ];

        // Create conversion from lead
        $conversion = $this->performanceService->createConversionFromLead($leadData, 7500);

        $this->assertNotNull($conversion);
        $this->assertEquals($this->user->id, $conversion->user_id);
        $this->assertEquals(7500, $conversion->conversion_amount);
        $this->assertEquals($this->target->id, $conversion->sales_target_id);
        $this->assertTrue($conversion->is_counted);
    }

    /** @test */
    public function it_updates_target_achieved_amount_from_conversions()
    {
        // Create multiple conversions
        SalesConversion::create([
            'lead_id' => 1,
            'user_id' => $this->user->id,
            'sales_target_id' => $this->target->id,
            'conversion_amount' => 3000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        SalesConversion::create([
            'lead_id' => 2,
            'user_id' => $this->user->id,
            'sales_target_id' => $this->target->id,
            'conversion_amount' => 2500,
            'conversion_date' => now(),
            'conversion_type' => 'renewal',
            'is_counted' => true,
        ]);

        // Update target achieved amount
        $this->target->updateAchievedAmount();

        $this->assertEquals(5500, $this->target->achieved_amount);
        $this->assertEquals(55, $this->target->progress_percentage);
    }

    /** @test */
    public function it_calculates_performance_metrics_correctly()
    {
        // Create performance record
        $performance = SalesPerformance::create([
            'entity_type' => 'individual',
            'entity_id' => $this->user->id,
            'entity_name' => $this->user->name,
            'sales_target_id' => $this->target->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'period_type' => 'monthly',
            'target_amount' => 10000,
            'achieved_amount' => 8000,
            'leads_count' => 20,
            'won_leads_count' => 8,
            'lost_leads_count' => 5,
            'calculated_at' => now(),
        ]);

        // Calculate metrics
        $performance->calculateAchievementPercentage();
        $performance->calculateConversionRate();
        $performance->calculateAverageDealSize();
        $performance->calculateScore();

        $this->assertEquals(80, $performance->achievement_percentage);
        $this->assertEquals(40, $performance->conversion_rate); // 8/20 * 100
        $this->assertEquals(1000, $performance->average_deal_size); // 8000/8
        $this->assertGreaterThan(0, $performance->score);
    }

    /** @test */
    public function it_prevents_double_counting_in_team_aggregation()
    {
        // Create second user and add to team
        $role = Role::first();
        $user2 = User::factory()->create(['role_id' => $role->id]);
        
        SalesTeamMember::create([
            'team_id' => $this->team->id,
            'user_id' => $user2->id,
            'role' => 'member',
            'role_name' => $role->name,
            'joined_at' => now()->subDays(20),
            'is_active' => true,
            'contribution_percentage' => 100.00,
        ]);

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

        // Create conversions for both users
        SalesConversion::create([
            'lead_id' => 1,
            'user_id' => $this->user->id,
            'sales_target_id' => $teamTarget->id,
            'conversion_amount' => 6000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        SalesConversion::create([
            'lead_id' => 2,
            'user_id' => $user2->id,
            'sales_target_id' => $teamTarget->id,
            'conversion_amount' => 4000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        // Calculate team performance
        $this->performanceService->calculatePerformanceForTarget($teamTarget);

        // Assert team total equals sum of individual contributions
        $teamPerformance = SalesPerformance::where('entity_type', 'team')
            ->where('entity_id', $this->team->id)
            ->first();

        $this->assertEquals(10000, $teamPerformance->achieved_amount); // 6000 + 4000

        // Assert individual performances exist and are linked
        $individualPerformances = SalesPerformance::where('entity_type', 'individual')
            ->where('parent_performance_id', $teamPerformance->id)
            ->get();

        $this->assertCount(2, $individualPerformances);
        
        $totalIndividualAchieved = $individualPerformances->sum('achieved_amount');
        $this->assertEquals($teamPerformance->achieved_amount, $totalIndividualAchieved);
    }

    /** @test */
    public function it_handles_multiple_targets_for_same_user()
    {
        // Create second target for same user
        $target2 = SalesTarget::factory()->create([
            'assignee_type' => 'individual',
            'assignee_id' => $this->user->id,
            'assignee_name' => $this->user->name,
            'target_amount' => 15000,
            'start_date' => now()->addMonth()->startOfMonth(),
            'end_date' => now()->addMonth()->endOfMonth(),
            'period_type' => 'monthly',
            'status' => 'active',
        ]);

        // Create conversions for both targets
        SalesConversion::create([
            'lead_id' => 1,
            'user_id' => $this->user->id,
            'sales_target_id' => $this->target->id,
            'conversion_amount' => 4000,
            'conversion_date' => now(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        SalesConversion::create([
            'lead_id' => 2,
            'user_id' => $this->user->id,
            'sales_target_id' => $target2->id,
            'conversion_amount' => 6000,
            'conversion_date' => now()->addMonth(),
            'conversion_type' => 'new_logo',
            'is_counted' => true,
        ]);

        // Calculate performance for both targets
        $this->performanceService->calculatePerformanceForTarget($this->target);
        $this->performanceService->calculatePerformanceForTarget($target2);

        // Assert separate performance records exist
        $performance1 = SalesPerformance::where('sales_target_id', $this->target->id)->first();
        $performance2 = SalesPerformance::where('sales_target_id', $target2->id)->first();

        $this->assertNotNull($performance1);
        $this->assertNotNull($performance2);
        $this->assertEquals(4000, $performance1->achieved_amount);
        $this->assertEquals(6000, $performance2->achieved_amount);
        $this->assertNotEquals($performance1->period_start, $performance2->period_start);
    }
}
