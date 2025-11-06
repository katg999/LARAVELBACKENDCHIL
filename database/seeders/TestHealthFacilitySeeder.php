<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HealthFacility;

class TestHealthFacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting to seed test health facilities...');

        // Check if Test Health Facility already exists
        $existingCount = HealthFacility::count();
        
        $facilities = [
            [
                'name' => 'Nairobi Medical Center',
                'email' => 'info@nairobimedical.co.ke',
                'contact' => '+254722345601',
            ],
            [
                'name' => 'Mombasa Health Clinic',
                'email' => 'contact@mombasaclinic.co.ke',
                'contact' => '+254722345602',
            ],
            [
                'name' => 'Kisumu General Hospital',
                'email' => 'admin@kisumuhospital.co.ke',
                'contact' => '+254722345603',
            ],
            [
                'name' => 'Eldoret Medical Centre',
                'email' => 'info@eldoretmedical.co.ke',
                'contact' => '+254722345604',
            ],
        ];

        foreach ($facilities as $facilityData) {
            $facility = HealthFacility::create($facilityData);
            $this->command->info("Created health facility: {$facility->name}");
        }

        $totalCount = HealthFacility::count();
        $newCount = $totalCount - $existingCount;
        
        $this->command->info("✓ Successfully created {$newCount} new health facilities!");
        $this->command->info("Total health facilities in database: {$totalCount}");
    }
}
