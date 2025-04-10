<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AppointmentSeeder extends Seeder
{
    public function run()
    {
        // Check if we have the required data first
        $students = Student::where('school_id', 1)->take(5)->get();
        $doctors = Doctor::all();

        if ($students->isEmpty()) {
            Log::warning('No students found for school_id 1. Skipping appointments creation.');
            $this->command->info('No students available, skipping appointments creation');
            return;
        }

        if ($doctors->isEmpty()) {
            Log::warning('No doctors found in the database. Skipping appointments creation.');
            $this->command->info('No doctors available, skipping appointments creation');
            return;
        }

        $reasons = [
            'Routine checkup',
            'Allergy symptoms',
            'Fever and cough',
            'Sports physical',
            'Skin rash',
            'Headache evaluation',
            'Anxiety concerns'
        ];

        foreach ($students as $student) {
            try {
                $doctor = $doctors->random();
                $daysFromNow = rand(1, 30);
                
                Appointment::create([
                    'school_id' => 1,
                    'student_id' => $student->id,
                    'doctor_id' => $doctor->id,
                    'appointment_time' => Carbon::now()->addDays($daysFromNow)
                        ->setHour(rand(9, 15))
                        ->setMinute(0),
                    'reason' => $reasons[array_rand($reasons)],
                    'status' => $this->getRandomStatus($daysFromNow),
                    'notes' => $this->getRandomNotes(),
                    'meeting_url' => $this->generateMeetingUrl()
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to create appointment for student {$student->id}: " . $e->getMessage());
                continue;
            }
        }

        $this->command->info('Created ' . count($students) . ' appointments');
    }

    private function getRandomStatus(int $daysFromNow): string
    {
        if ($daysFromNow < 7) {
            return ['pending', 'approved'][rand(0, 1)];
        }
        return ['pending', 'approved', 'completed', 'cancelled'][rand(0, 3)];
    }

    private function getRandomNotes(): ?string
    {
        $notes = [
            null,
            'Student has history of similar symptoms',
            'Parent requested specific doctor',
            'Follow-up appointment',
            'Urgent care needed',
            'Brought to attention by school nurse'
        ];
        return $notes[array_rand($notes)];
    }

    private function generateMeetingUrl(): ?string
    {
        return rand(0, 1) ? 'https://meet.ketiai.com/'.bin2hex(random_bytes(5)) : null;
    }
}