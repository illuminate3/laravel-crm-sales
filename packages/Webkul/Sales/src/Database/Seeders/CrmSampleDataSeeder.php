<?php

namespace Webkul\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CrmSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedOrganizations();
        $this->seedPersons();
        $this->seedLeads();
    }

    /**
     * Seed organizations table.
     */
    protected function seedOrganizations(): void
    {
        $organizations = [
            [
                'name' => 'Tech Solutions Inc.',
                'address' => '123 Business Ave, Tech City, TC 12345',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Global Marketing Corp',
                'address' => '456 Marketing St, Business Town, BT 67890',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Innovation Labs',
                'address' => '789 Innovation Blvd, Future City, FC 11111',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Digital Dynamics',
                'address' => '321 Digital Way, Online City, OC 22222',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Enterprise Solutions Ltd',
                'address' => '654 Enterprise Dr, Corporate Town, CT 33333',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('organizations')->insert($organizations);
    }

    /**
     * Seed persons table.
     */
    protected function seedPersons(): void
    {
        $organizations = DB::table('organizations')->pluck('id')->toArray();
        
        $persons = [
            [
                'name' => 'John Smith',
                'emails' => json_encode(['john.smith@techsolutions.com']),
                'contact_numbers' => json_encode(['555-0101']),
                'organization_id' => $organizations[0] ?? null,
                'job_title' => 'CEO',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sarah Johnson',
                'emails' => json_encode(['sarah.johnson@globalmarketing.com']),
                'contact_numbers' => json_encode(['555-0102']),
                'organization_id' => $organizations[1] ?? null,
                'job_title' => 'Marketing Director',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mike Davis',
                'emails' => json_encode(['mike.davis@innovationlabs.com']),
                'contact_numbers' => json_encode(['555-0103']),
                'organization_id' => $organizations[2] ?? null,
                'job_title' => 'CTO',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Emily Wilson',
                'emails' => json_encode(['emily.wilson@digitaldynamics.com']),
                'contact_numbers' => json_encode(['555-0104']),
                'organization_id' => $organizations[3] ?? null,
                'job_title' => 'Product Manager',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Robert Brown',
                'emails' => json_encode(['robert.brown@enterprisesolutions.com']),
                'contact_numbers' => json_encode(['555-0105']),
                'organization_id' => $organizations[4] ?? null,
                'job_title' => 'VP Sales',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('persons')->insert($persons);
    }

    /**
     * Seed leads table.
     */
    protected function seedLeads(): void
    {
        $persons = DB::table('persons')->pluck('id')->toArray();
        $pipeline = DB::table('lead_pipelines')->first();
        $stages = DB::table('lead_pipeline_stages')->where('lead_pipeline_id', $pipeline->id)->pluck('id')->toArray();
        $sources = DB::table('lead_sources')->pluck('id')->toArray();
        $types = DB::table('lead_types')->pluck('id')->toArray();
        $userId = DB::table('users')->value('id');

        $leads = [
            [
                'title' => 'Enterprise Software Solution',
                'description' => 'Looking for comprehensive enterprise software solution for 500+ employees',
                'lead_value' => 150000.00,
                'user_id' => $userId,
                'person_id' => $persons[0] ?? null,
                'lead_source_id' => $sources[0] ?? 1,
                'lead_type_id' => $types[0] ?? 1,
                'lead_pipeline_id' => $pipeline->id,
                'lead_pipeline_stage_id' => $stages[0] ?? 1,
                'expected_close_date' => now()->addDays(30),
                'created_at' => now()->subDays(10),
                'updated_at' => now(),
            ],
            [
                'title' => 'Digital Marketing Campaign',
                'description' => 'Need comprehensive digital marketing strategy and implementation',
                'lead_value' => 75000.00,
                'user_id' => $userId,
                'person_id' => $persons[1] ?? null,
                'lead_source_id' => $sources[0] ?? 1,
                'lead_type_id' => $types[0] ?? 1,
                'lead_pipeline_id' => $pipeline->id,
                'lead_pipeline_stage_id' => $stages[1] ?? 1,
                'expected_close_date' => now()->addDays(45),
                'created_at' => now()->subDays(8),
                'updated_at' => now(),
            ],
            [
                'title' => 'Cloud Infrastructure Migration',
                'description' => 'Migrate existing infrastructure to cloud-based solution',
                'lead_value' => 200000.00,
                'user_id' => $userId,
                'person_id' => $persons[2] ?? null,
                'lead_source_id' => $sources[0] ?? 1,
                'lead_type_id' => $types[0] ?? 1,
                'lead_pipeline_id' => $pipeline->id,
                'lead_pipeline_stage_id' => $stages[0] ?? 1,
                'expected_close_date' => now()->addDays(60),
                'created_at' => now()->subDays(5),
                'updated_at' => now(),
            ],
            [
                'title' => 'Mobile App Development',
                'description' => 'Custom mobile application for iOS and Android platforms',
                'lead_value' => 120000.00,
                'user_id' => $userId,
                'person_id' => $persons[3] ?? null,
                'lead_source_id' => $sources[0] ?? 1,
                'lead_type_id' => $types[0] ?? 1,
                'lead_pipeline_id' => $pipeline->id,
                'lead_pipeline_stage_id' => $stages[2] ?? 1,
                'expected_close_date' => now()->addDays(90),
                'created_at' => now()->subDays(3),
                'updated_at' => now(),
            ],
            [
                'title' => 'CRM Implementation',
                'description' => 'Implementation and customization of CRM system',
                'lead_value' => 85000.00,
                'user_id' => $userId,
                'person_id' => $persons[4] ?? null,
                'lead_source_id' => $sources[0] ?? 1,
                'lead_type_id' => $types[0] ?? 1,
                'lead_pipeline_id' => $pipeline->id,
                'lead_pipeline_stage_id' => $stages[1] ?? 1,
                'expected_close_date' => now()->addDays(75),
                'created_at' => now()->subDays(1),
                'updated_at' => now(),
            ],
        ];

        DB::table('leads')->insert($leads);
    }
}
