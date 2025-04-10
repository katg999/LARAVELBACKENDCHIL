<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School; // Import your models
use App\Models\Student;
use App\Models\Specialization;
use App\Models\Doctor;
use App\Models\LabTest;


class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            SchoolSeeder::class,
            SpecializationSeeder::class,
            DoctorSeeder::class,
            StudentSeeder::class,
            LabTestSeeder::class,
            LabRequestSeeder::class,
            AppointmentSeeder::class,
        ]);
    }
}