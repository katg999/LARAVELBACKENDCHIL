<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\School;
use App\Models\HealthFacility;
use App\Mail\AppointmentApprovalMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentApprovalTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function doctor_can_mark_appointment_as_completed_and_it_becomes_awaiting_approval()
    {
        Mail::fake();

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

        $appointment = Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'school_id' => $school->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Regular checkup',
            'status' => 'confirmed', // Assuming appointment is confirmed/paid
        ]);

        // Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'status' => 'confirmed'
        ]);

        // Make request to complete appointment
        $response = $this->withoutMiddleware()
            ->patchJson(route('appointments.complete', $appointment));

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'status' => 'awaiting_approval'
                ]);

        // Verify appointment status changed
        $appointment->refresh();
        $this->assertEquals('awaiting_approval', $appointment->status);

        // Verify email was sent
        Mail::assertSent(AppointmentApprovalMail::class, function ($mail) use ($appointment) {
            return $mail->appointment->id === $appointment->id;
        });
    }

    /** @test */
    public function institution_can_approve_completed_appointment()
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

        $appointment = Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'school_id' => $school->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Regular checkup',
            'status' => 'awaiting_approval',
        ]);

        // Make request to approve appointment
        $response = $this->withoutMiddleware()
            ->patchJson(route('appointments.approve', $appointment));

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'status' => 'completed'
                ]);

        // Verify appointment status changed to completed
        $appointment->refresh();
        $this->assertEquals('completed', $appointment->status);
    }

    /** @test */
    public function cannot_approve_appointment_that_is_not_awaiting_approval()
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

        $appointment = Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'school_id' => $school->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Regular checkup',
            'status' => 'confirmed', // Not awaiting approval
        ]);

        // Try to approve appointment
        $response = $this->withoutMiddleware()
            ->patchJson(route('appointments.approve', $appointment));

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false
                ]);

        // Verify appointment status didn't change
        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);
    }

    /** @test */
    public function approval_email_contains_correct_information()
    {
        // Create test data
        $school = School::create([
            'name' => 'Test School',
            'email' => 'test@school.com',
            'contact' => '+256700000000',
        ]);

        $doctor = Doctor::create([
            'name' => 'Dr. Smith',
            'email' => 'dr@smith.com',
            'specialization' => 'General',
            'contact' => '+256700000001',
            'school_id' => $school->id,
        ]);

        $duration = \App\Models\Duration::create([
            'minutes' => 45,
            'duration_type' => 'general',
            'price' => 50000,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'patient_id' => 'P123456',
            'name' => 'John Doe',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'school_id' => $school->id,
        ]);

        $appointment = Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'school_id' => $school->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1)->setTime(14, 30),
            'reason' => 'Annual checkup',
            'status' => 'confirmed',
        ]);

        // Test email content
        $mail = new AppointmentApprovalMail($appointment);

        $this->assertEquals('Appointment Completed - Awaiting Your Approval', $mail->envelope()->subject);

        $renderedContent = $mail->render();
        $this->assertStringContainsString('Test School', $renderedContent);
        $this->assertStringContainsString('Dr. Smith', $renderedContent);
        $this->assertStringContainsString('John Doe', $renderedContent);
        $this->assertStringContainsString('Annual checkup', $renderedContent);
    }

    /** @test */
    public function health_facility_appointments_can_be_approved()
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
            'minutes' => 30,
            'duration_type' => 'general',
            'price' => 50000,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'patient_id' => 'P654321',
            'name' => 'Test HF Patient',
            'birth_date' => '1985-05-15',
            'gender' => 'female',
            'health_facility_id' => $healthFacility->id,
        ]);

        $appointment = Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'health_facility_id' => $healthFacility->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Follow-up consultation',
            'status' => 'awaiting_approval',
        ]);

        // Make request to approve appointment
        $response = $this->withoutMiddleware()
            ->patchJson(route('appointments.approve', $appointment));

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'status' => 'completed'
                ]);

        // Verify appointment status changed to completed
        $appointment->refresh();
        $this->assertEquals('completed', $appointment->status);
    }

    /** @test */
    public function web_approval_request_works_for_schools()
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

        $appointment = Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'school_id' => $school->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Regular checkup',
            'status' => 'awaiting_approval',
        ]);

        // Make web request to approve appointment
        $response = $this->withoutMiddleware()
            ->patch(route('appointments.approve', $appointment));

        $response->assertRedirect()
                ->assertSessionHas('success', 'Appointment approved successfully');

        // Verify appointment status changed to completed
        $appointment->refresh();
        $this->assertEquals('completed', $appointment->status);
    }
}