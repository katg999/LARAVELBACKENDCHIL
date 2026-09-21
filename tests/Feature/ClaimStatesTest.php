<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consent;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\HealthFacility;
use App\Models\Insurer;
use App\Models\MemberPolicy;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimStatesTest extends TestCase
{
    use RefreshDatabase;

    private HealthFacility $clinic;
    private HealthFacility $other;
    private Doctor $doctor;
    private Duration $duration;
    private Insurer $insurer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clinic = $this->facility();
        $this->other = $this->facility();
        $this->doctor = Doctor::create(['name' => 'Claim Doctor', 'email' => 'cd@t.com', 'specialization' => 'GP', 'contact' => '1', 'meeting_slug' => 'cd']);
        $this->duration = Duration::create(['minutes' => 30, 'duration_type' => 'general', 'price' => 50000, 'is_active' => true]);
        $this->insurer = Insurer::create(['name' => 'Jubilee', 'code' => 'jubilee']);
    }

    private function facility(): HealthFacility
    {
        return HealthFacility::create(['name' => 'C' . uniqid(), 'email' => uniqid() . '@t.com', 'contact_number' => '1', 'contact' => '1', 'location' => 'K', 'type' => 'clinic']);
    }

    private function as(HealthFacility $f)
    {
        return $this->withSession(['authenticated_user' => ['type' => 'health_facility', 'id' => $f->id, 'name' => 'C', 'email' => 'c@t.com']]);
    }

    private function visit(array $over = [], ?HealthFacility $f = null): Appointment
    {
        $f ??= $this->clinic;
        $p = Patient::create(['patient_id' => 'P' . random_int(100000, 999999), 'name' => 'Pat' . uniqid(), 'gender' => 'female', 'birth_date' => '1990-01-01', 'health_facility_id' => $f->id]);
        $policy = MemberPolicy::create(['patient_id' => $p->id, 'insurer_id' => $this->insurer->id, 'member_number' => 'M-' . uniqid()]);

        return Appointment::create($over + [
            'health_facility_id' => $f->id, 'patient_id' => $p->id, 'doctor_id' => $this->doctor->id, 'duration_id' => $this->duration->id,
            'appointment_time' => now()->subDay(), 'reason' => 'x', 'status' => 'completed', 'coverage_type' => 'insurance',
            'member_policy_id' => $policy->id, 'insurance_status' => 'verified', 'visit_code' => 'VC-1',
        ]);
    }

    private function medicine(array $over = [], ?HealthFacility $f = null): Prescription
    {
        $f ??= $this->clinic;
        $p = Patient::create(['patient_id' => 'P' . random_int(100000, 999999), 'name' => 'Rx' . uniqid(), 'gender' => 'female', 'birth_date' => '1990-01-01', 'health_facility_id' => $f->id]);
        $policy = MemberPolicy::create(['patient_id' => $p->id, 'insurer_id' => $this->insurer->id, 'member_number' => 'M-' . uniqid()]);

        return Prescription::create($over + [
            'patient_id' => $p->id, 'source' => 'issued', 'status' => 'issued', 'coverage_type' => 'insurance', 'member_policy_id' => $policy->id,
            'total_amount' => 10000, 'insurer_amount' => 7000, 'patient_amount' => 3000, 'insurance_status' => 'approved', 'payment_status' => 'paid',
        ]);
    }

    // ---- the export fix ----

    /** @test */
    public function only_visits_that_actually_happened_are_exported_as_claims(): void
    {
        $attended = $this->visit(['status' => 'completed']);
        $joined = $this->visit(['status' => 'confirmed']);
        Consent::create(['patient_id' => $joined->patient_id, 'appointment_id' => $joined->id, 'type' => 'video_visit', 'granted_at' => now()]);
        $cancelled = $this->visit(['status' => 'cancelled']);
        $future = $this->visit(['status' => 'confirmed', 'appointment_time' => now()->addDays(2)]);   // verified but nobody has attended

        $csv = $this->as($this->clinic)->get('/insurance/visit-records.csv')->assertOk()->streamedContent();

        $this->assertStringContainsString((string) $attended->patient->name, $csv);
        $this->assertStringContainsString((string) $joined->patient->name, $csv);
        $this->assertStringNotContainsString($cancelled->patient->name, $csv);
        $this->assertStringNotContainsString($future->patient->name, $csv);
    }

    /** @test */
    public function cancelled_or_unattended_visits_cannot_be_marked_submitted(): void
    {
        $ok = $this->visit(['status' => 'completed']);
        $cancelled = $this->visit(['status' => 'cancelled']);
        $future = $this->visit(['status' => 'confirmed']);

        $this->as($this->clinic)->postJson('/insurance/visit-records/submitted', ['appointment_ids' => [$ok->id, $cancelled->id, $future->id]])
            ->assertOk()->assertJson(['submitted' => 1]);

        $this->assertSame('submitted', $ok->fresh()->claim_status);
        $this->assertNotNull($ok->fresh()->claim_submitted_at);
        $this->assertNull($cancelled->fresh()->claim_status);
        $this->assertSame('verified', $future->fresh()->insurance_status);
    }

    /** @test */
    public function medicine_whose_delivery_was_cancelled_is_not_claimed(): void
    {
        $good = $this->medicine();
        $cancelled = $this->medicine(['delivery_status' => 'cancelled']);

        $csv = $this->as($this->clinic)->get('/care/pharmacy/claims.csv')->assertOk()->streamedContent();

        $this->assertStringContainsString($good->patient->name, $csv);
        $this->assertStringNotContainsString($cancelled->patient->name, $csv);
    }

    // ---- claim states ----

    private function submitted(): Appointment
    {
        $a = $this->visit();
        $this->as($this->clinic)->postJson('/insurance/visit-records/submitted', ['appointment_ids' => [$a->id]])->assertOk();

        return $a->fresh();
    }

    private function reply(Appointment $a, array $body, ?HealthFacility $f = null)
    {
        return $this->as($f ?? $this->clinic)->postJson("/insurance/appointments/{$a->id}/claim-response", $body);
    }

    /** @test */
    public function a_visit_claim_follows_submitted_accepted_paid(): void
    {
        $a = $this->submitted();

        $this->reply($a, ['status' => 'accepted', 'reference' => 'JUB-CLM-9'])->assertOk();
        $this->assertSame('accepted', $a->fresh()->claim_status);
        $this->assertSame('JUB-CLM-9', $a->fresh()->claim_reference);

        $this->reply($a, ['status' => 'paid'])->assertStatus(409);                              // the amount paid is required
        $this->reply($a, ['status' => 'paid', 'amount_paid' => 48000])->assertOk();

        $a->refresh();
        $this->assertSame('paid', $a->claim_status);
        $this->assertEquals(48000, $a->claim_paid_amount);
        $this->reply($a, ['status' => 'rejected', 'note' => 'late'])->assertStatus(409);        // paid is closed
        $this->assertDatabaseHas('audit_logs', ['action' => 'claim.visit.paid', 'subject_id' => $a->id]);
    }

    /** @test */
    public function a_query_needs_a_reason_and_can_be_corrected_and_sent_again(): void
    {
        $a = $this->submitted();

        $this->reply($a, ['status' => 'queried'])->assertStatus(409);
        $this->reply($a, ['status' => 'queried', 'note' => 'Diagnosis code missing'])->assertOk();
        $this->assertSame('Diagnosis code missing', $a->fresh()->claim_note);

        $this->reply($a, ['status' => 'submitted', 'note' => 'Code added'])->assertOk();
        $a->refresh();
        $this->assertSame('submitted', $a->claim_status);
        $this->assertSame('Code added', $a->claim_note);
        $this->assertNotNull($a->claim_submitted_at);
    }

    /** @test */
    public function a_rejection_needs_a_reason_and_can_be_appealed(): void
    {
        $a = $this->submitted();

        $this->reply($a, ['status' => 'rejected'])->assertStatus(409);
        $this->reply($a, ['status' => 'rejected', 'note' => 'Not covered'])->assertOk();
        $this->reply($a, ['status' => 'paid', 'amount_paid' => 1])->assertStatus(409);           // cannot jump from rejected to paid
        $this->reply($a, ['status' => 'submitted'])->assertOk();
    }

    /** @test */
    public function replies_are_refused_for_claims_not_yet_submitted_and_for_other_clinics(): void
    {
        $notSent = $this->visit();
        $this->reply($notSent, ['status' => 'accepted'])->assertStatus(409);

        $a = $this->submitted();
        $this->reply($a, ['status' => 'accepted'], $this->other)->assertNotFound();
        $this->reply($a, ['status' => 'nonsense'])->assertStatus(422);
        $this->assertSame('submitted', $a->fresh()->claim_status);
    }

    /** @test */
    public function medicine_claims_follow_the_same_states(): void
    {
        $rx = $this->medicine();
        $this->as($this->clinic)->postJson('/care/pharmacy/claims/submitted', ['prescription_ids' => [$rx->id]])->assertOk();
        $this->assertSame('submitted', $rx->fresh()->claim_status);

        $send = fn (array $b, $f = null) => $this->as($f ?? $this->clinic)->postJson("/insurance/prescriptions/{$rx->id}/claim-response", $b);
        $send(['status' => 'accepted'], $this->other)->assertNotFound();
        $send(['status' => 'accepted', 'reference' => 'PA-5'])->assertOk();
        $send(['status' => 'paid', 'amount_paid' => 7000])->assertOk();

        $this->assertSame('paid', $rx->fresh()->claim_status);
        $this->assertEquals(7000, $rx->fresh()->claim_paid_amount);
    }

    // ---- the outstanding report ----

    /** @test */
    public function the_report_shows_totals_by_status_and_flags_claims_waiting_too_long(): void
    {
        $old = $this->submitted();
        $old->update(['claim_submitted_at' => now()->subDays(45)]);
        $paid = $this->submitted();
        $this->reply($paid, ['status' => 'paid', 'amount_paid' => 50000])->assertOk();
        $rx = $this->medicine();
        $this->as($this->clinic)->postJson('/care/pharmacy/claims/submitted', ['prescription_ids' => [$rx->id]]);
        $rx->fresh()->update(['claim_status' => 'queried']);
        $theirs = $this->visit([], $this->other);
        $this->as($this->other)->postJson('/insurance/visit-records/submitted', ['appointment_ids' => [$theirs->id]]);

        $r = $this->as($this->clinic)->getJson('/insurance/claims')->assertOk()->json();

        $this->assertSame(1, $r['summary']['submitted']['count']);
        $this->assertSame(1, $r['summary']['queried']['count']);
        $this->assertSame(1, $r['summary']['paid']['count']);
        $this->assertEquals(50000, $r['summary']['paid']['paid_ugx']);
        $this->assertEquals(50000 + 7000, $r['outstanding_ugx']);                        // the old visit and the queried medicine
        $this->assertCount(1, $r['overdue']);                                             // only the 45-day-old visit
        $this->assertSame($old->id, $r['overdue'][0]['id']);
        $this->assertGreaterThanOrEqual(45, $r['overdue'][0]['days_waiting']);
        $this->assertCount(3, $r['claims']);                                             // not the other clinic's

        $filtered = $this->as($this->clinic)->getJson('/insurance/claims?status=paid')->assertOk()->json();
        $this->assertCount(1, $filtered['claims']);
        $this->as($this->clinic)->getJson('/insurance/claims?status=bogus')->assertStatus(422);
    }
}
