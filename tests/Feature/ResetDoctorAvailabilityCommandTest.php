<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ResetDoctorAvailabilityCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_resets_doctor_availability_for_all_doctors()
    {
        // Create test doctors
        $doctor1 = Doctor::create([
            'name' => 'Dr. Test One',
            'email' => 'test1@example.com',
            'specialization' => 'General Practitioner',
            'contact' => '123456789'
        ]);

        $doctor2 = Doctor::create([
            'name' => 'Dr. Test Two',
            'email' => 'test2@example.com',
            'specialization' => 'Cardiologist',
            'contact' => '987654321'
        ]);

        // Create some existing availability (should be cleared)
        DoctorAvailability::create([
            'doctor_id' => $doctor1->id,
            'day' => 'monday',
            'available' => false,
            'max_appointments' => 5
        ]);

        // Run the command
        $this->artisan('doctors:reset-availability')
            ->expectsOutput('Resetting doctor availability for the next week...')
            ->expectsOutput('Successfully reset availability for 2 doctors.')
            ->expectsOutput('Created 10 availability records for Monday-Friday.')
            ->expectsOutput('Doctors are now available for booking.')
            ->assertExitCode(0);

        // Assert that old availability was cleared and new ones created
        $this->assertDatabaseCount('doctor_availabilities', 10); // 2 doctors × 5 days

        // Check that all doctors are available for weekdays
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        foreach ([$doctor1, $doctor2] as $doctor) {
            foreach ($weekdays as $day) {
                $this->assertDatabaseHas('doctor_availabilities', [
                    'doctor_id' => $doctor->id,
                    'day' => $day,
                    'available' => true,
                    'max_appointments' => 10
                ]);
            }
        }
    }

    public function test_command_handles_no_doctors_gracefully()
    {
        // Ensure no doctors exist
        Doctor::query()->delete();

        $this->artisan('doctors:reset-availability')
            ->expectsOutput('Resetting doctor availability for the next week...')
            ->expectsOutput('No doctors found in the system.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('doctor_availabilities', 0);
    }

    public function test_command_with_week_option()
    {
        // Create a test doctor
        Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'test@example.com',
            'specialization' => 'General Practitioner',
            'contact' => '123456789'
        ]);

        $this->artisan('doctors:reset-availability --week=current')
            ->expectsOutput('Resetting doctor availability for the current week...')
            ->assertExitCode(0);

        $this->assertDatabaseCount('doctor_availabilities', 5); // 1 doctor × 5 days
    }
}
