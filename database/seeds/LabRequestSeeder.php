<?php

namespace Database\Seeders;

use App\Models\LabRequest;
use App\Models\Student;
use Illuminate\Database\Seeder;

class LabRequestSeeder extends Seeder
{
    public function run()
    {
        $students = Student::where('school_id', 1)->take(5)->get();
        $tests = [1, 2, 3]; // Blood Test, Urinalysis, Allergy Screening

        foreach ($students as $student) {
            LabRequest::create([
                'school_id' => 1,
                'student_id' => $student->id,
                'lab_test_id' => $tests[array_rand($tests)],
                'notes' => 'Routine health checkup',
                'status' => ['pending', 'processing', 'completed'][rand(0, 2)],
                'results' => $this->generateRandomResults()
            ]);
        }
    }

    private function generateRandomResults(): ?string
    {
        $results = [
            null,
            'All results within normal range',
            'Mild allergy detected - recommend follow up',
            'Slightly elevated white blood cell count',
            'Normal urinalysis results',
            'Vision 20/20, perfect eyesight',
            'Mild hearing loss in left ear'
        ];

        return $results[array_rand($results)];
    }
}