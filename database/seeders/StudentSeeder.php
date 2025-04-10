<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run()
    {
        $students = [
            [
                'school_id' => 1,
                'name' => 'John Doe',
                'email' => 'john.doe@student.greenwood.edu',
                'class' => 'Grade 5',
                'date_of_birth' => '2012-05-15',
                'medical_notes' => 'Allergic to peanuts'
            ],
            [
                'school_id' => 1,
                'name' => 'Jane Smith',
                'email' => 'jane.smith@student.greenwood.edu',
                'class' => 'Grade 6',
                'date_of_birth' => '2011-08-22',
                'medical_notes' => 'Asthma - carries inhaler'
            ],
            // Add more students
        ];

        foreach ($students as $student) {
            Student::create($student);
        }
    }
}