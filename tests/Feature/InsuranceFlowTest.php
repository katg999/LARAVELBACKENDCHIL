<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\HealthFacility;
use App\Models\Insurer;
use App\Models\MemberPolicy;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class InsuranceFlowTest extends TestCase
{
    use RefreshDatabase;

    private HealthFacility $clinic;
    private HealthFacility $otherClinic;
    private Doctor $doctor;
    private Duration $duration;
    private Insurer $insurer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clinic = $this->facility('Mine');
        $this->otherClinic = $this->facility('Other');
        $this->doctor = Doctor::create([
            'name' => 'Ins Doctor', 'email' => 'ins@test.com', 'specialization' => 'General Practitioner',
            'contact' => '256722222222', 'meeting_slug' => 'ins-doc',
        ]);
        $this->duration = Duration::create(['minutes' => 30, 'duration_type' => 'general', 'price' => 50000, 'is_active' => true]);
        $this->insurer = Insurer::create(['name' => 'Jubilee', 'code' => 'jubilee']);
    }

    private function facility(string $name): HealthFacility
    {
        return HealthFacility::create([
            'name' => $name, 'email' => uniqid() . '@test.com', 'contact_number' => '256711111111',
            'contact' => '256711111111', 'location' => 'Kampala', 'type' => 'clinic',
        ]);
    }

    private function patient(HealthFacility $f, string $name = 'Pat'): Patient
    {
        return Patient::create([
            'patient_id' => 'P' . random_int(100000, 999999), 'name' => $name, 'gender' => 'female',
            'birth_date' => '1990-01-01', 'health_facility_id' => $f->id, 'contact_number' => '0772123456',
        ]);
    }

    private function asClinic(HealthFacility $f)
    {
        return $this->withSession(['authenticated_user' => ['type' => 'health_facility', 'id' => $f->id, 'name' => $f->name, 'email' => $f->email]]);
    }

    private function insuredAppointment(HealthFacility $f, Patient $p, string $member = 'M-1'): Appointment
    {
        $policy = MemberPolicy::create(['patient_id' => $p->id, 'insurer_id' => $this->insurer->id, 'member_number' => $member]);

        return Appointment::create([
            'health_facility_id' => $f->id, 'patient_id' => $p->id, 'doctor_id' => $this->doctor->id,
            'duration_id' => $this->duration->id, 'appointment_time' => now()->addDay(), 'reason' => 'Fever',
            'status' => 'awaiting_verification', 'coverage_type' => 'insurance', 'member_policy_id' => $policy->id,
            'visit_code' => 'VC-' . $member, 'insurance_status' => 'pending',
        ]);
    }

    /** @test */
    public function staff_can_register_a_policy_only_for_their_own_patients(): void
    {
        $mine = $this->patient($this->clinic);
        $theirs = $this->patient($this->otherClinic);
        $body = ['insurer_id' => $this->insurer->id, 'member_number' => 'JUB-100'];

        $this->asClinic($this->clinic)->postJson("/insurance/patients/{$mine->id}/policies", $body)->assertCreated();
        $this->assertDatabaseHas('member_policies', ['patient_id' => $mine->id, 'member_number' => 'JUB-100']);

        $this->asClinic($this->clinic)->postJson("/insurance/patients/{$theirs->id}/policies", ['member_number' => 'X'] + $body)->assertForbidden();

        // same member number cannot be claimed by a different patient
        $second = $this->patient($this->clinic, 'Second');
        $this->asClinic($this->clinic)->postJson("/insurance/patients/{$second->id}/policies", $body)->assertStatus(422);
    }

    /** @test */
    public function insured_booking_waits_for_verification_and_rejects_foreign_policies(): void
    {
        $p = $this->patient($this->clinic);
        $policy = MemberPolicy::create(['patient_id' => $p->id, 'insurer_id' => $this->insurer->id, 'member_number' => 'M-9']);
        $stranger = MemberPolicy::create(['patient_id' => $this->patient($this->clinic, 'Other Pat')->id, 'insurer_id' => $this->insurer->id, 'member_number' => 'M-10']);

        $base = [
            'doctor_id' => $this->doctor->id, 'duration_id' => $this->duration->id,
            'appointment_time' => now()->addDays(2)->setTime(10, 0)->toDateTimeString(),
            'reason' => 'Cough', 'patient_id' => $p->id, 'health_facility_id' => $this->clinic->id,
        ];

        $this->postJson('/appointments', $base + ['member_policy_id' => $stranger->id, 'visit_code' => 'V1'])->assertStatus(422);

        $this->postJson('/appointments', $base + ['member_policy_id' => $policy->id, 'visit_code' => 'V2']);
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $p->id, 'coverage_type' => 'insurance', 'status' => 'awaiting_verification',
            'insurance_status' => 'pending', 'visit_code' => 'V2',
        ]);
    }

    /** @test */
    public function verifying_confirms_the_visit_and_texts_the_patient(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 201)]);
        config(['services.africastalking.username' => 'u', 'services.africastalking.api_key' => 'k']);
        $a = $this->insuredAppointment($this->clinic, $this->patient($this->clinic));

        $this->asClinic($this->clinic)->postJson("/insurance/appointments/{$a->id}/verify", ['result' => 'verified'])->assertOk();

        $a->refresh();
        $this->assertSame('confirmed', $a->status);
        $this->assertSame('insurance', $a->payment_status);
        $this->assertSame('verified', $a->insurance_status);
        $this->assertNotNull($a->memberPolicy->verified_at);
        Http::assertSent(fn ($r) => str_contains($r['message'], '/visit/' . $a->id));

        // cannot verify twice
        $this->asClinic($this->clinic)->postJson("/insurance/appointments/{$a->id}/verify", ['result' => 'verified'])->assertStatus(409);
    }

    /** @test */
    public function declining_falls_back_to_self_pay(): void
    {
        $a = $this->insuredAppointment($this->clinic, $this->patient($this->clinic));

        $this->asClinic($this->clinic)->postJson("/insurance/appointments/{$a->id}/verify", ['result' => 'rejected', 'note' => 'Policy lapsed'])->assertOk();

        $a->refresh();
        $this->assertSame('awaiting_payment', $a->status);
        $this->assertSame('self_pay', $a->coverage_type);
        $this->assertSame('rejected', $a->insurance_status);
        $this->assertSame('Policy lapsed', $a->insurance_note);
    }

    /** @test */
    public function other_clinics_and_patients_cannot_verify_or_export(): void
    {
        $a = $this->insuredAppointment($this->clinic, $this->patient($this->clinic));

        $this->asClinic($this->otherClinic)->postJson("/insurance/appointments/{$a->id}/verify", ['result' => 'verified'])->assertNotFound();

        $this->withSession(['authenticated_user' => ['type' => 'school', 'id' => 1, 'name' => 'S', 'email' => 's@t.com']])
            ->get('/insurance/visit-records.csv')->assertForbidden();
        $this->flushSession();
        $this->get('/insurance/visit-records.csv')->assertOk(); // unauthenticated shows the logged-out page, never data
        $this->assertStringNotContainsString('VC-M-1', (string) $this->get('/insurance/visit-records.csv')->getContent());
    }

    /** @test */
    public function csv_export_lists_only_my_verified_visits_and_submitted_ones_drop_out(): void
    {
        $mine = $this->insuredAppointment($this->clinic, $this->patient($this->clinic, 'Grace'), 'M-1');
        $pending = $this->insuredAppointment($this->clinic, $this->patient($this->clinic, 'Pending'), 'M-2');
        $theirs = $this->insuredAppointment($this->otherClinic, $this->patient($this->otherClinic, 'Stranger'), 'M-3');
        foreach ([$mine, $theirs] as $a) {
            $a->update(['insurance_status' => 'verified', 'status' => 'confirmed']);
        }

        $csv = $this->asClinic($this->clinic)->get('/insurance/visit-records.csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('Grace', $csv);
        $this->assertStringContainsString('VC-M-1', $csv);
        $this->assertStringContainsString('Jubilee', $csv);
        $this->assertStringNotContainsString('Stranger', $csv);
        $this->assertStringNotContainsString('Pending', $csv);

        $this->asClinic($this->clinic)->postJson('/insurance/visit-records/submitted', ['appointment_ids' => [$mine->id, $theirs->id]])
            ->assertOk()->assertJson(['submitted' => 1]);

        $this->assertSame('submitted', $mine->fresh()->insurance_status);
        $this->assertSame('verified', $theirs->fresh()->insurance_status);
        $this->assertStringNotContainsString('Grace', $this->asClinic($this->clinic)->get('/insurance/visit-records.csv')->streamedContent());
        $this->assertStringContainsString('Grace', $this->asClinic($this->clinic)->get('/insurance/visit-records.csv?all=1')->streamedContent());
    }

    /** @test */
    public function patient_visit_page_says_insurance_is_being_checked(): void
    {
        $a = $this->insuredAppointment($this->clinic, $this->patient($this->clinic));
        $url = URL::temporarySignedRoute('visit.show', now()->addDay(), ['appointment' => $a->id]);

        $this->get($url)->assertOk()->assertSee('Checking your insurance')->assertDontSee($a->meeting_room);
    }
}
