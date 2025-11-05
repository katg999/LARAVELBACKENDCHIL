<?php

namespace Tests\Unit;

use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DoctorAvailabilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_doctor_scope_available_on_day_returns_correct_doctors()
    {
        // Create doctors
        $doctor1 = Doctor::create([
            'name' => 'Dr. Monday',
            'email' => 'monday@example.com',
            'specialization' => 'General Practitioner',
            'contact' => '111111111'
        ]);

        $doctor2 = Doctor::create([
            'name' => 'Dr. Tuesday',
            'email' => 'tuesday@example.com',
            'specialization' => 'Cardiologist',
            'contact' => '222222222'
        ]);

        $doctor3 = Doctor::create([
            'name' => 'Dr. Unavailable',
            'email' => 'unavailable@example.com',
            'specialization' => 'Dentist',
            'contact' => '333333333'
        ]);

        // Set availability
        DoctorAvailability::create(['doctor_id' => $doctor1->id, 'day' => 'monday', 'available' => true, 'max_appointments' => 10]);
        DoctorAvailability::create(['doctor_id' => $doctor2->id, 'day' => 'tuesday', 'available' => true, 'max_appointments' => 10]);
        DoctorAvailability::create(['doctor_id' => $doctor3->id, 'day' => 'monday', 'available' => false, 'max_appointments' => 5]);

        // Test scope
        $mondayDoctors = Doctor::availableOnDay('monday')->get();
        $tuesdayDoctors = Doctor::availableOnDay('tuesday')->get();

        $this->assertCount(1, $mondayDoctors);
        $this->assertEquals($doctor1->id, $mondayDoctors->first()->id);

        $this->assertCount(1, $tuesdayDoctors);
        $this->assertEquals($doctor2->id, $tuesdayDoctors->first()->id);
    }

    public function test_doctor_is_available_on_day_method()
    {
        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'test@example.com',
            'specialization' => 'General Practitioner',
            'contact' => '123456789'
        ]);

        // Create availability for Monday
        DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'day' => 'monday',
            'available' => true,
            'max_appointments' => 10
        ]);

        // Test availability checks
        $this->assertTrue($doctor->isAvailableOnDay('monday'));
        $this->assertFalse($doctor->isAvailableOnDay('tuesday'));
        $this->assertTrue($doctor->isAvailableOnDay('MONDAY')); // Case insensitive
    }

    public function test_doctor_availability_relationship()
    {
        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'test@example.com',
            'specialization' => 'General Practitioner',
            'contact' => '123456789'
        ]);

        // Create multiple availability records
        DoctorAvailability::create(['doctor_id' => $doctor->id, 'day' => 'monday', 'available' => true, 'max_appointments' => 10]);
        DoctorAvailability::create(['doctor_id' => $doctor->id, 'day' => 'tuesday', 'available' => true, 'max_appointments' => 10]);
        DoctorAvailability::create(['doctor_id' => $doctor->id, 'day' => 'wednesday', 'available' => false, 'max_appointments' => 5]);

        $doctor->load('availabilities');

        $this->assertCount(3, $doctor->availabilities);
        $this->assertEquals(2, $doctor->availabilities->where('available', true)->count());
        $this->assertEquals(1, $doctor->availabilities->where('available', false)->count());
    }

    public function test_doctor_scope_available_on_day_handles_case_insensitive_input()
    {
        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'test@example.com',
            'specialization' => 'General Practitioner',
            'contact' => '123456789'
        ]);

        DoctorAvailability::create([
            'doctor_id' => $doctor->id,
            'day' => 'monday',
            'available' => true,
            'max_appointments' => 10
        ]);

        // Test with different cases
        $this->assertCount(1, Doctor::availableOnDay('monday')->get());
        $this->assertCount(1, Doctor::availableOnDay('MONDAY')->get());
        $this->assertCount(1, Doctor::availableOnDay('Monday')->get());
    }
}
