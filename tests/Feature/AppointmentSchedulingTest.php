<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\School;
use App\Models\HealthFacility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentSchedulingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_schedule_appointment_via_ajax_for_school_patient()
    {
        // Create test data
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);
        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'dr@test.com',
            'specialization' => 'General',
            'contact' => '+256700000001',
            'school_id' => $school->id,
        ]);
        $duration = \App\Models\Duration::create([
            'minutes' => 30,
            'duration_type' => 'general',
            'price' => 50000,
            'is_active' => true,
        ]);
        $patient = Patient::create([
            'patient_id' => 'P123456',
            'name' => 'Test Patient',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
        ]);

        $appointmentData = [
            'doctor_id' => $doctor->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(7)->setTime(15, 0)->format('Y-m-d\TH:i'), // 7 days from now at 3 PM
            'reason' => 'Regular checkup',
            'patient_id' => $patient->id,
            'school_id' => $school->id
        ];

        // Make AJAX request without CSRF middleware
        $response = $this->withoutMiddleware()->postJson(route('appointments.store'), $appointmentData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Appointment scheduled successfully'
                ]);

        // Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'school_id' => $school->id,
            'duration_id' => $duration->id,
            'reason' => 'Regular checkup',
            'status' => 'awaiting_payment'
        ]);
    }

    /** @test */
    public function it_can_schedule_appointment_via_ajax_for_health_facility_patient()
    {
        // Create test data
        $healthFacility = HealthFacility::create([
            'name' => 'Test Health Facility',
            'email' => 'test@facility.com',
            'contact_number' => '+256711111111',
            'contact' => '+256711111111',
            'location' => 'Test Location',
            'type' => 'hospital',
        ]);
        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'dr@test.com',
            'specialization' => 'General',
            'contact' => '+256700000001',
            'health_facility_id' => $healthFacility->id,
        ]);
        $duration = \App\Models\Duration::create([
            'minutes' => 45,
            'duration_type' => 'specialist',
            'price' => 100000,
            'is_active' => true,
        ]);
        $patient = Patient::create([
            'patient_id' => 'P654321',
            'name' => 'Test HF Patient',
            'birth_date' => '1985-05-15',
            'gender' => 'female',
            'health_facility_id' => $healthFacility->id,
        ]);

        $appointmentData = [
            'doctor_id' => $doctor->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(7)->setTime(16, 30)->format('Y-m-d\TH:i'), // 7 days from now at 4:30 PM
            'reason' => 'Follow-up consultation',
            'patient_id' => $patient->id,
            'health_facility_id' => $healthFacility->id
        ];

        // Make AJAX request without CSRF middleware
        $response = $this->withoutMiddleware()->postJson(route('appointments.store'), $appointmentData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Appointment scheduled successfully'
                ]);

        // Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'health_facility_id' => $healthFacility->id,
            'duration_id' => $duration->id,
            'reason' => 'Follow-up consultation',
            'status' => 'awaiting_payment'
        ]);
    }

    /** @test */
    public function it_validates_appointment_scheduling_data()
    {
        $patient = Patient::create([
            'patient_id' => 'P999999',
            'name' => 'Test Patient',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
        ]);

        $invalidData = [
            'doctor_id' => '', // Required
            'duration_id' => '', // Required
            'appointment_time' => 'invalid-datetime', // Invalid datetime
            'reason' => '', // Required
            'patient_id' => $patient->id
        ];

        $response = $this->withoutMiddleware()->postJson(route('appointments.store'), $invalidData);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false
                ])
                ->assertJsonStructure([
                    'errors' => [
                        'doctor_id',
                        'duration_id',
                        'appointment_time',
                        'reason'
                    ]
                ]);
    }

    /** @test */
    public function it_prevents_scheduling_appointments_in_the_past()
    {
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);
        $doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'dr@test.com',
            'specialization' => 'General',
            'contact' => '+256700000001',
            'school_id' => $school->id,
        ]);
        $duration = \App\Models\Duration::create([
            'minutes' => 30,
            'duration_type' => 'general',
            'price' => 50000,
            'is_active' => true,
        ]);
        $patient = Patient::create([
            'patient_id' => 'P111111',
            'name' => 'Test Patient',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
        ]);

        $appointmentData = [
            'doctor_id' => $doctor->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->subHours(1)->format('Y-m-d\TH:i'), // 1 hour in the past
            'reason' => 'Past appointment',
            'patient_id' => $patient->id,
            'school_id' => $school->id
        ];

        $response = $this->withoutMiddleware()->postJson(route('appointments.store'), $appointmentData);

        $response->assertStatus(422)
                ->assertJsonStructure(['errors'])
                ->assertJsonFragment([
                    'appointment_time' => ['Cannot schedule appointments in the past.']
                ]);
    }
}