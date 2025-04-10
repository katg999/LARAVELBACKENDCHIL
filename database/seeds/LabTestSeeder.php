<?php

namespace Database\Seeders;

use App\Models\LabTest;
use Illuminate\Database\Seeder;

class LabTestSeeder extends Seeder
{
    public function run()
    {
        $tests = [
            [
                'name' => 'Blood Test',
                'description' => 'Complete blood count (CBC) and basic metabolic panel'
            ],
            [
                'name' => 'Urinalysis',
                'description' => 'Analysis of urine for various disorders'
            ],
            [
                'name' => 'Allergy Screening',
                'description' => 'Test for common environmental and food allergies'
            ],
            [
                'name' => 'Vision Test',
                'description' => 'Basic eyesight examination'
            ],
            [
                'name' => 'Hearing Test',
                'description' => 'Basic hearing ability examination'
            ],
            [
                'name' => 'X-Ray',
                'description' => 'Basic radiographic imaging'
            ],
            [
                'name' => 'Dental Checkup',
                'description' => 'Oral health examination'
            ]
        ];

        foreach ($tests as $test) {
            LabTest::create($test);
        }
    }
}