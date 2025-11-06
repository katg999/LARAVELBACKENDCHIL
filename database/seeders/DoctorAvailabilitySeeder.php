<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Illuminate\Database\Seeder;

class DoctorAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $doctors = Doctor::all();

        $availabilityPatterns = [
            // Pattern 1: Monday, Wednesday, Friday (General Medicine)
            [
                ['day' => 'monday', 'available' => true, 'max_appointments' => 8],
                ['day' => 'tuesday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'wednesday', 'available' => true, 'max_appointments' => 8],
                ['day' => 'thursday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'friday', 'available' => true, 'max_appointments' => 8],
                ['day' => 'saturday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'sunday', 'available' => false, 'max_appointments' => 0],
            ],
            // Pattern 2: Tuesday, Thursday, Saturday (Pediatrics)
            [
                ['day' => 'monday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'tuesday', 'available' => true, 'max_appointments' => 6],
                ['day' => 'wednesday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'thursday', 'available' => true, 'max_appointments' => 6],
                ['day' => 'friday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'saturday', 'available' => true, 'max_appointments' => 4],
                ['day' => 'sunday', 'available' => false, 'max_appointments' => 0],
            ],
            // Pattern 3: Monday to Friday (Cardiology)
            [
                ['day' => 'monday', 'available' => true, 'max_appointments' => 5],
                ['day' => 'tuesday', 'available' => true, 'max_appointments' => 5],
                ['day' => 'wednesday', 'available' => true, 'max_appointments' => 5],
                ['day' => 'thursday', 'available' => true, 'max_appointments' => 5],
                ['day' => 'friday', 'available' => true, 'max_appointments' => 5],
                ['day' => 'saturday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'sunday', 'available' => false, 'max_appointments' => 0],
            ],
            // Pattern 4: Tuesday, Wednesday, Thursday (Dermatology)
            [
                ['day' => 'monday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'tuesday', 'available' => true, 'max_appointments' => 7],
                ['day' => 'wednesday', 'available' => true, 'max_appointments' => 7],
                ['day' => 'thursday', 'available' => true, 'max_appointments' => 7],
                ['day' => 'friday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'saturday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'sunday', 'available' => false, 'max_appointments' => 0],
            ],
            // Pattern 5: Monday, Thursday, Friday (Orthopedics)
            [
                ['day' => 'monday', 'available' => true, 'max_appointments' => 6],
                ['day' => 'tuesday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'wednesday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'thursday', 'available' => true, 'max_appointments' => 6],
                ['day' => 'friday', 'available' => true, 'max_appointments' => 6],
                ['day' => 'saturday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'sunday', 'available' => false, 'max_appointments' => 0],
            ],
            // Pattern 6: Wednesday, Friday, Saturday (Gynecology)
            [
                ['day' => 'monday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'tuesday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'wednesday', 'available' => true, 'max_appointments' => 5],
                ['day' => 'thursday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'friday', 'available' => true, 'max_appointments' => 5],
                ['day' => 'saturday', 'available' => true, 'max_appointments' => 3],
                ['day' => 'sunday', 'available' => false, 'max_appointments' => 0],
            ],
            // Pattern 7: Monday, Tuesday, Friday (Ophthalmology)
            [
                ['day' => 'monday', 'available' => true, 'max_appointments' => 7],
                ['day' => 'tuesday', 'available' => true, 'max_appointments' => 7],
                ['day' => 'wednesday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'thursday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'friday', 'available' => true, 'max_appointments' => 7],
                ['day' => 'saturday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'sunday', 'available' => false, 'max_appointments' => 0],
            ],
            // Pattern 8: Tuesday, Thursday (Dentistry)
            [
                ['day' => 'monday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'tuesday', 'available' => true, 'max_appointments' => 8],
                ['day' => 'wednesday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'thursday', 'available' => true, 'max_appointments' => 8],
                ['day' => 'friday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'saturday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'sunday', 'available' => false, 'max_appointments' => 0],
            ],
            // Pattern 9: Monday, Wednesday (Psychiatry)
            [
                ['day' => 'monday', 'available' => true, 'max_appointments' => 4],
                ['day' => 'tuesday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'wednesday', 'available' => true, 'max_appointments' => 4],
                ['day' => 'thursday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'friday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'saturday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'sunday', 'available' => false, 'max_appointments' => 0],
            ],
            // Pattern 10: Thursday, Friday, Saturday (Neurology)
            [
                ['day' => 'monday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'tuesday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'wednesday', 'available' => false, 'max_appointments' => 0],
                ['day' => 'thursday', 'available' => true, 'max_appointments' => 5],
                ['day' => 'friday', 'available' => true, 'max_appointments' => 5],
                ['day' => 'saturday', 'available' => true, 'max_appointments' => 3],
                ['day' => 'sunday', 'available' => false, 'max_appointments' => 0],
            ],
        ];

        foreach ($doctors as $index => $doctor) {
            $patternIndex = $index % count($availabilityPatterns);
            $pattern = $availabilityPatterns[$patternIndex];

            foreach ($pattern as $availability) {
                DoctorAvailability::create([
                    'doctor_id' => $doctor->id,
                    'day' => $availability['day'],
                    'available' => $availability['available'],
                    'max_appointments' => $availability['max_appointments'],
                ]);
            }
        }
    }
}