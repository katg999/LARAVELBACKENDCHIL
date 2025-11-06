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

class AppointmentApprovalEmailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function approval_email_is_sent_when_doctor_completes_appointment()
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
            'status' => 'confirmed',
        ]);

        // Make request to complete appointment
        $this->withoutMiddleware()
            ->patchJson(route('appointments.complete', $appointment));

        // Assert that email was sent
        Mail::assertSent(AppointmentApprovalMail::class, function ($mail) use ($appointment) {
            return $mail->appointment->id === $appointment->id;
        });

        // Assert email was sent to the institution
        Mail::assertSent(AppointmentApprovalMail::class, 1);
    }

    /** @test */
    public function approval_email_contains_correct_recipient_for_school_appointments()
    {
        Mail::fake();

        // Create test data
        $school = School::create([
            'name' => 'Test School',
            'email' => 'school@test.com',
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
            'status' => 'confirmed',
        ]);

        // Make request to complete appointment
        $this->withoutMiddleware()
            ->patchJson(route('appointments.complete', $appointment));

        // Assert email was sent with correct data
        Mail::assertSent(AppointmentApprovalMail::class, function ($mail) use ($school, $appointment) {
            return $mail->institution->id === $school->id &&
                   $mail->appointment->id === $appointment->id;
        });
    }

    /** @test */
    public function approval_email_contains_correct_recipient_for_health_facility_appointments()
    {
        Mail::fake();

        // Create test data
        $healthFacility = HealthFacility::create([
            'name' => 'Test Health Facility',
            'email' => 'facility@test.com',
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
            'status' => 'confirmed',
        ]);

        // Make request to complete appointment
        $this->withoutMiddleware()
            ->patchJson(route('appointments.complete', $appointment));

        // Assert email was sent with correct data
        Mail::assertSent(AppointmentApprovalMail::class, function ($mail) use ($healthFacility, $appointment) {
            return $mail->institution->id === $healthFacility->id &&
                   $mail->appointment->id === $appointment->id;
        });
    }

    /** @test */
    public function email_content_includes_all_necessary_appointment_details()
    {
        // Create test data
        $school = School::create([
            'name' => 'Green Valley School',
            'email' => 'school@test.com',
            'contact' => '+256700000000',
        ]);

        $doctor = Doctor::create([
            'name' => 'Dr. Sarah Johnson',
            'email' => 'dr@test.com',
            'specialization' => 'Pediatrics',
            'contact' => '+256700000001',
            'school_id' => $school->id,
        ]);

        $duration = \App\Models\Duration::create([
            'minutes' => 45,
            'duration_type' => 'specialist',
            'price' => 75000,
            'is_active' => true,
        ]);

        $patient = Patient::create([
            'patient_id' => 'P123456',
            'name' => 'Emma Wilson',
            'birth_date' => '2012-03-15',
            'gender' => 'female',
            'school_id' => $school->id,
        ]);

        $appointmentTime = now()->addDays(2)->setTime(10, 30);
        $appointment = Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'school_id' => $school->id,
            'duration_id' => $duration->id,
            'appointment_time' => $appointmentTime,
            'reason' => 'Annual health screening and vaccination update',
            'status' => 'confirmed',
        ]);

        $mail = new AppointmentApprovalMail($appointment);

        // Test envelope
        $this->assertEquals('Appointment Completed - Awaiting Your Approval', $mail->envelope()->subject);

        // Test content data
        $content = $mail->content();
        $this->assertEquals('emails.appointment-approval', $content->view);

        // Verify data passed to view
        $viewData = $content->with;
        $this->assertEquals($appointment->id, $viewData['appointment']->id);
        $this->assertEquals($doctor->id, $viewData['doctor']->id);
        $this->assertEquals($patient->id, $viewData['patient']->id);
        $this->assertEquals($school->id, $viewData['institution']->id);
    }

    /** @test */
    public function email_is_not_sent_if_appointment_completion_fails()
    {
        Mail::fake();

        // Create appointment with invalid data that would cause completion to fail
        $appointment = new Appointment([
            'doctor_id' => 999, // Non-existent doctor
            'patient_id' => 999, // Non-existent patient
            'appointment_time' => now()->addDays(1),
            'reason' => 'Test',
            'status' => 'confirmed',
        ]);

        // Try to complete non-existent appointment
        $response = $this->withoutMiddleware()
            ->patchJson(route('appointments.complete', 999));

        $response->assertStatus(404);

        // Assert no email was sent
        Mail::assertNotSent(AppointmentApprovalMail::class);
    }
}