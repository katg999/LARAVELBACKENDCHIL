<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use App\Models\School;
use App\Models\HealthFacility;
use Carbon\Carbon;

class TestPatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting to seed test patients...');

        // Get all schools and health facilities
        $schools = School::all();
        $healthFacilities = HealthFacility::all();

        if ($schools->isEmpty() && $healthFacilities->isEmpty()) {
            $this->command->warn('No schools or health facilities found. Please seed schools and health facilities first.');
            return;
        }

        // Sample names
        $maleNames = ['James', 'John', 'Robert', 'Michael', 'William', 'David', 'Richard', 'Joseph', 'Thomas', 'Charles'];
        $femaleNames = ['Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara', 'Susan', 'Jessica', 'Sarah', 'Karen'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];

        // Seed patients for schools
        foreach ($schools as $school) {
            $this->command->info("Seeding patients for school: {$school->name}");
            
            // Create 15-25 students per school
            $studentCount = rand(15, 25);
            
            for ($i = 0; $i < $studentCount; $i++) {
                $gender = rand(0, 1) ? 'male' : 'female';
                $firstName = $gender === 'male' ? $maleNames[array_rand($maleNames)] : $femaleNames[array_rand($femaleNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $name = $firstName . ' ' . $lastName;
                
                // Age between 5 and 18 years
                $age = rand(5, 18);
                $birthDate = Carbon::now()->subYears($age)->subDays(rand(0, 364));
                
                // Grade based on age
                $grade = max(1, min(12, $age - 4));
                
                Patient::create([
                    'name' => $name,
                    'gender' => $gender,
                    'birth_date' => $birthDate,
                    'contact_number' => '+254' . rand(700000000, 799999999),
                    'parent_contact' => '+254' . rand(700000000, 799999999),
                    'grade' => $grade,
                    'school_id' => $school->id,
                    'medical_history' => $this->generateRandomMedicalHistory(),
                ]);
            }
            
            $this->command->info("Created {$studentCount} students for {$school->name}");
        }

        // Seed patients for health facilities
        foreach ($healthFacilities as $facility) {
            $this->command->info("Seeding patients for health facility: {$facility->name}");
            
            // Create 20-30 patients per health facility
            $patientCount = rand(20, 30);
            
            for ($i = 0; $i < $patientCount; $i++) {
                $gender = rand(0, 1) ? 'male' : 'female';
                $firstName = $gender === 'male' ? $maleNames[array_rand($maleNames)] : $femaleNames[array_rand($femaleNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $name = $firstName . ' ' . $lastName;
                
                // Age between 0 and 80 years (all ages for health facility)
                $age = rand(0, 80);
                $birthDate = Carbon::now()->subYears($age)->subDays(rand(0, 364));
                
                Patient::create([
                    'name' => $name,
                    'gender' => $gender,
                    'birth_date' => $birthDate,
                    'contact_number' => '+254' . rand(700000000, 799999999),
                    'parent_contact' => $age < 18 ? '+254' . rand(700000000, 799999999) : null,
                    'health_facility_id' => $facility->id,
                    'medical_history' => $this->generateRandomMedicalHistory(),
                ]);
            }
            
            $this->command->info("Created {$patientCount} patients for {$facility->name}");
        }

        $totalPatients = Patient::count();
        $this->command->info("✓ Seeding complete! Total patients in database: {$totalPatients}");
    }

    /**
     * Generate random medical history for variety
     */
    private function generateRandomMedicalHistory(): ?string
    {
        $conditions = [
            'No known allergies',
            'Allergic to penicillin',
            'Asthma',
            'Diabetes Type 1',
            'Diabetes Type 2',
            'Hypertension',
            'No significant medical history',
            'Seasonal allergies',
            'Food allergies (peanuts)',
            'Lactose intolerant',
        ];

        // 30% chance of having no medical history
        if (rand(1, 10) <= 3) {
            return null;
        }

        // Return 1-2 conditions
        $selectedConditions = array_rand(array_flip($conditions), rand(1, 2));
        
        if (is_array($selectedConditions)) {
            return implode(', ', $selectedConditions);
        }
        
        return $selectedConditions;
    }
}
