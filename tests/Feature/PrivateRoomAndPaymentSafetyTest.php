<?php

namespace Tests\Feature;

use App\Mail\AppointmentConfirmationMail;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\HealthFacility;
use App\Models\Patient;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PrivateRoomAndPaymentSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointment(array $patientOverrides = [], string $doctorSlug = 'shared-slug'): Appointment
    {
        $facility = HealthFacility::create([
            'name' => 'Clinic', 'email' => uniqid('c') . '@test.com', 'contact_number' => '256711111111',
            'contact' => '256711111111', 'location' => 'Kampala', 'type' => 'clinic',
        ]);
        $doctor = Doctor::firstOrCreate(['email' => 'doc@test.com'], [
            'name' => 'Dr. Room', 'specialization' => 'General Practitioner',
            'contact' => '256722222222', 'meeting_slug' => $doctorSlug,
        ]);
        $duration = Duration::firstOrCreate(['minutes' => 30, 'duration_type' => 'general'], [
            'price' => 50000, 'is_active' => true,
        ]);
        $patient = Patient::create(array_merge([
            'patient_id' => 'P' . random_int(100000, 999999), 'name' => 'Pat', 'gender' => 'female',
            'birth_date' => '1990-01-01', 'health_facility_id' => $facility->id,
            'contact_number' => '0772123456',
        ], $patientOverrides));

        return Appointment::create([
            'health_facility_id' => $facility->id, 'patient_id' => $patient->id,
            'doctor_id' => $doctor->id, 'duration_id' => $duration->id,
            'appointment_time' => now()->addDay(), 'reason' => 'Check', 'status' => 'awaiting_payment',
        ]);
    }

    /** @test */
    public function each_appointment_gets_its_own_unguessable_room_not_the_doctors_shared_one(): void
    {
        $a = $this->makeAppointment();
        $b = $this->makeAppointment();

        $this->assertNotEmpty($a->meeting_room);
        $this->assertNotEquals($a->meeting_room, $b->meeting_room);
        $this->assertGreaterThanOrEqual(24, strlen($a->meeting_room));
        $this->assertStringNotContainsString('shared-slug', $a->meeting_url);
    }

    /** @test */
    public function doctor_email_links_to_the_appointment_room(): void
    {
        $a = $this->makeAppointment();
        $mail = new AppointmentConfirmationMail($a, $a->doctor, $a->patient, $a->healthFacility);

        $this->assertEquals($a->meeting_url, $mail->meetingLink);
    }

    /** @test */
    public function dummy_payment_route_is_disabled_outside_local_and_testing(): void
    {
        $a = $this->makeAppointment();
        $this->app['env'] = 'production';
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->postJson(route('payment.appointment.confirm-dummy', $a))->assertNotFound();
        $this->assertEquals('awaiting_payment', $a->fresh()->status);
    }

    /** @test */
    public function ugandan_numbers_are_normalised(): void
    {
        $sms = new SmsService();

        $this->assertSame('+256772123456', $sms->normalizeUgandanNumber('0772123456'));
        $this->assertSame('+256772123456', $sms->normalizeUgandanNumber('256772123456'));
        $this->assertSame('+256772123456', $sms->normalizeUgandanNumber('+256 772 123 456'));
        $this->assertNull($sms->normalizeUgandanNumber('12345'));
        $this->assertNull($sms->normalizeUgandanNumber(null));
    }

    /** @test */
    public function sms_is_sent_through_provider_when_configured_and_skipped_when_not(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 201)]);

        $this->assertFalse((new SmsService())->send('0772123456', 'hi'));
        Http::assertNothingSent();

        config(['services.africastalking.username' => 'u', 'services.africastalking.api_key' => 'k']);
        $this->assertTrue((new SmsService())->send('0772123456', 'hi'));
        Http::assertSent(fn ($r) => $r['to'] === '+256772123456' && $r->hasHeader('apiKey', 'k'));
    }
}
