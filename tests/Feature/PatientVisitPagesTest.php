<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\HealthFacility;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PatientVisitPagesTest extends TestCase
{
    use RefreshDatabase;

    private function appointment($time, string $status = 'confirmed', ?Patient $patient = null): Appointment
    {
        $facility = HealthFacility::firstOrCreate(['email' => 'f@test.com'], [
            'name' => 'Clinic', 'contact_number' => '256711111111', 'contact' => '256711111111',
            'location' => 'Kampala', 'type' => 'clinic',
        ]);
        $doctor = Doctor::firstOrCreate(['email' => 'd@test.com'], [
            'name' => 'Visit', 'specialization' => 'General Practitioner', 'contact' => '256722222222',
            'meeting_slug' => 'shared-doc-room',
        ]);
        $duration = Duration::firstOrCreate(['minutes' => 30, 'duration_type' => 'general'], ['price' => 50000, 'is_active' => true]);
        $patient ??= Patient::create([
            'patient_id' => 'P' . random_int(100000, 999999), 'name' => 'Grace', 'gender' => 'female',
            'birth_date' => '1990-01-01', 'health_facility_id' => $facility->id, 'contact_number' => '0772123456',
        ]);

        return Appointment::create([
            'health_facility_id' => $facility->id, 'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'duration_id' => $duration->id, 'appointment_time' => $time, 'reason' => 'Check',
            'status' => $status, 'payment_status' => $status === 'confirmed' ? 'completed' : null,
        ]);
    }

    private function link(Appointment $a): string
    {
        return URL::temporarySignedRoute('visit.show', now()->addDay(), ['appointment' => $a->id]);
    }

    /** @test */
    public function unsigned_or_tampered_links_are_rejected(): void
    {
        $a = $this->appointment(now()->addMinutes(5));

        $this->get("/visit/{$a->id}")->assertForbidden();
        $this->get($this->link($a) . 'x')->assertForbidden();
    }

    /** @test */
    public function room_is_revealed_only_inside_the_joining_window_of_a_paid_appointment(): void
    {
        $open = $this->appointment(now()->addMinutes(5));
        $this->get($this->link($open))->assertOk()->assertSee($open->meeting_room)->assertSee('Join video visit');

        $early = $this->appointment(now()->addDay());
        $this->get($this->link($early))->assertOk()->assertDontSee($early->meeting_room)->assertSee('Not open yet');

        $ended = $this->appointment(now()->subHours(5));
        $this->get($this->link($ended))->assertOk()->assertDontSee($ended->meeting_room)->assertSee('Visit ended');

        $unpaid = $this->appointment(now()->addMinutes(5), 'awaiting_payment');
        $this->get($this->link($unpaid))->assertOk()->assertDontSee($unpaid->meeting_room)->assertSee('Waiting for payment');

        $cancelled = $this->appointment(now()->addMinutes(5), 'cancelled');
        $this->get($this->link($cancelled))->assertOk()->assertDontSee($cancelled->meeting_room)->assertSee('Cancelled');
    }

    /** @test */
    public function my_visits_lists_only_that_patients_appointments(): void
    {
        $mine = $this->appointment(now()->addDay());
        $other = $this->appointment(now()->addDays(2));   // different patient

        $url = URL::temporarySignedRoute('patient.visits', now()->addDay(), ['patient' => $mine->patient_id]);
        $body = $this->get($url)->assertOk()->getContent();

        $this->assertStringContainsString('/visit/' . $mine->id, $body);
        $this->assertStringNotContainsString('/visit/' . $other->id, $body);
        $this->get("/my-visits/{$mine->patient_id}")->assertForbidden();
    }

    /** @test */
    public function payment_sms_contains_a_signed_visit_link_not_the_raw_room(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 201)]);
        config(['services.africastalking.username' => 'u', 'services.africastalking.api_key' => 'k']);
        $a = $this->appointment(now()->addDay());

        $m = new \ReflectionMethod(PaymentController::class, 'sendPatientJoinLink');
        $m->setAccessible(true);
        $m->invoke(app(PaymentController::class), $a->fresh());

        Http::assertSent(function ($r) use ($a) {
            return str_contains($r['message'], '/visit/' . $a->id)
                && str_contains($r['message'], 'signature=')
                && !str_contains($r['message'], 'meet.jit.si')
                && !str_contains($r['message'], $a->meeting_room);
        });
    }
}
