<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Duration;
use App\Models\HealthFacility;
use App\Models\Insurer;
use App\Models\MemberPolicy;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PatientInsuranceSelfRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private HealthFacility $clinic;
    private HealthFacility $other;
    private Patient $patient;
    private Insurer $insurer;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Http::fake(['*' => Http::response(['ok' => true], 201)]);
        config(['services.africastalking.username' => 'u', 'services.africastalking.api_key' => 'k']);
        $this->clinic = $this->facility();
        $this->other = $this->facility();
        $this->patient = $this->patient($this->clinic);
        $this->insurer = Insurer::create(['name' => 'Jubilee', 'code' => 'jubilee']);
    }

    private function facility(): HealthFacility
    {
        return HealthFacility::create(['name' => 'C' . uniqid(), 'email' => uniqid() . '@t.com', 'contact_number' => '1', 'contact' => '1', 'location' => 'K', 'type' => 'clinic']);
    }

    private function patient(HealthFacility $f): Patient
    {
        return Patient::create(['patient_id' => 'P' . random_int(100000, 999999), 'name' => 'Pat' . uniqid(), 'gender' => 'female', 'birth_date' => '1990-01-01', 'health_facility_id' => $f->id, 'contact_number' => '0772123456']);
    }

    private function submitUrl(?Patient $p = null): string
    {
        return URL::temporarySignedRoute('patient.policies.store', now()->addHour(), ['patient' => ($p ?? $this->patient)->id]);
    }

    private function body(array $over = []): array
    {
        return $over + ['insurer_id' => $this->insurer->id, 'member_number' => 'JUB-555', 'card' => UploadedFile::fake()->image('card.jpg')];
    }

    private function as(HealthFacility $f)
    {
        return $this->withSession(['authenticated_user' => ['type' => 'health_facility', 'id' => $f->id, 'name' => 'C', 'email' => 'c@t.com']]);
    }

    /** @test */
    public function a_patient_sends_their_details_from_the_link_and_it_waits_for_review(): void
    {
        $this->post("/my-visits/{$this->patient->id}/insurance", $this->body())->assertForbidden();

        $this->post($this->submitUrl(), $this->body())->assertRedirect()->assertSessionHas('insurance_status');

        $policy = MemberPolicy::firstOrFail();
        $this->assertSame('pending_review', $policy->status);
        $this->assertSame('patient', $policy->submitted_by);
        $this->assertSame($this->patient->id, $policy->patient_id);
        Storage::disk('local')->assertExists($policy->card_image_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'insurance.policy.submitted', 'actor_type' => 'patient', 'actor_id' => $this->patient->id]);
    }

    /** @test */
    public function bad_input_and_odd_files_are_refused(): void
    {
        $this->post($this->submitUrl(), $this->body(['insurer_id' => 999]))->assertSessionHasErrors('insurer_id');
        $this->post($this->submitUrl(), $this->body(['member_number' => '']))->assertSessionHasErrors('member_number');
        $this->post($this->submitUrl(), $this->body(['card' => UploadedFile::fake()->create('x.exe', 10)]))->assertSessionHasErrors('card');
        $this->assertSame(0, MemberPolicy::count());
    }

    /** @test */
    public function someone_elses_member_number_is_never_revealed_or_taken(): void
    {
        $stranger = $this->patient($this->other);
        MemberPolicy::create(['patient_id' => $stranger->id, 'insurer_id' => $this->insurer->id, 'member_number' => 'JUB-555', 'status' => 'active']);

        $r = $this->post($this->submitUrl(), $this->body());

        $this->assertStringNotContainsString('another', (string) session('insurance_status'));
        $this->assertSame(1, MemberPolicy::count());
        $this->assertSame($stranger->id, MemberPolicy::first()->patient_id);
        $this->assertNotEmpty($r->getSession()->get('insurance_status'));

        // resending your own is polite, not an error
        $this->post($this->submitUrl($stranger), $this->body());
        $this->assertSame('You already sent these details.', session('insurance_status'));
    }

    /** @test */
    public function a_pending_policy_cannot_be_used_to_book_until_staff_confirm_it(): void
    {
        $this->post($this->submitUrl(), $this->body());
        $policy = MemberPolicy::first();
        $doctor = Doctor::create(['name' => 'D', 'email' => 'd@t.com', 'specialization' => 'GP', 'contact' => '1', 'meeting_slug' => 'd']);
        $duration = Duration::create(['minutes' => 30, 'duration_type' => 'general', 'price' => 50000, 'is_active' => true]);
        $book = fn () => $this->postJson('/appointments', [
            'doctor_id' => $doctor->id, 'duration_id' => $duration->id, 'appointment_time' => now()->addDays(2)->setTime(10, 0)->toDateTimeString(),
            'reason' => 'x', 'patient_id' => $this->patient->id, 'health_facility_id' => $this->clinic->id, 'member_policy_id' => $policy->id,
        ]);

        $book()->assertStatus(422);

        $this->as($this->clinic)->postJson("/insurance/policies/{$policy->id}/review", ['result' => 'approved'])->assertOk();
        $book()->assertSuccessful();
        $this->assertDatabaseHas('appointments', ['member_policy_id' => $policy->id, 'coverage_type' => 'insurance', 'insurance_status' => 'pending']);
    }

    /** @test */
    public function staff_see_only_their_own_pending_policies_and_review_them_once(): void
    {
        $this->post($this->submitUrl(), $this->body());
        $this->post($this->submitUrl($this->patient($this->other)), $this->body(['member_number' => 'OTHER-1']));
        $mine = MemberPolicy::where('member_number', 'JUB-555')->first();

        $rows = $this->as($this->clinic)->getJson('/insurance/policies/pending')->assertOk()->json();
        $this->assertCount(1, $rows);
        $this->assertSame('JUB-555', $rows[0]['member_number']);
        $this->assertTrue($rows[0]['has_card']);

        $this->as($this->other)->postJson("/insurance/policies/{$mine->id}/review", ['result' => 'approved'])->assertNotFound();

        $this->as($this->clinic)->postJson("/insurance/policies/{$mine->id}/review", ['result' => 'approved'])->assertOk()->assertJson(['status' => 'active']);
        $mine->refresh();
        $this->assertSame('active', $mine->status);
        $this->assertNotNull($mine->verified_at);
        Http::assertSent(fn ($r) => str_contains($r['message'] ?? '', 'Jubilee cover is confirmed'));

        $this->as($this->clinic)->postJson("/insurance/policies/{$mine->id}/review", ['result' => 'rejected'])->assertStatus(409);
        $this->assertDatabaseHas('audit_logs', ['action' => 'insurance.policy.approved', 'subject_id' => $mine->id]);
    }

    /** @test */
    public function a_rejection_carries_the_reason_to_the_patient(): void
    {
        $this->post($this->submitUrl(), $this->body());
        $policy = MemberPolicy::first();

        $this->as($this->clinic)->postJson("/insurance/policies/{$policy->id}/review", ['result' => 'rejected', 'note' => 'Member number not found'])->assertOk();

        $this->assertSame('rejected', $policy->fresh()->status);
        Http::assertSent(fn ($r) => str_contains($r['message'] ?? '', 'Member number not found'));
        $url = URL::temporarySignedRoute('patient.visits', now()->addHour(), ['patient' => $this->patient->id]);
        $this->get($url)->assertSee('Not confirmed')->assertSee('Member number not found');
    }

    /** @test */
    public function the_card_photo_is_only_shown_to_staff_who_can_see_the_patient(): void
    {
        $this->post($this->submitUrl(), $this->body());
        $policy = MemberPolicy::first();

        $this->as($this->clinic)->get("/insurance/policies/{$policy->id}/card")->assertOk();
        $this->as($this->other)->get("/insurance/policies/{$policy->id}/card")->assertNotFound();
        $this->assertDatabaseHas('audit_logs', ['action' => 'insurance.card.viewed', 'subject_id' => $policy->id]);
    }

    /** @test */
    public function a_signed_in_patient_adds_insurance_for_themselves_only(): void
    {
        $stranger = $this->patient($this->other);
        $session = ['patient_auth' => ['phone' => '+256772123456', 'patient_ids' => [$this->patient->id]]];

        $this->withSession($session)->post("/patient/insurance/{$this->patient->id}", $this->body())->assertRedirect(route('patient.records'));
        $this->assertSame(1, MemberPolicy::count());

        $this->withSession($session)->post("/patient/insurance/{$stranger->id}", $this->body(['member_number' => 'X-2']))->assertForbidden();
        $this->flushSession();
        $this->post("/patient/insurance/{$this->patient->id}", $this->body(['member_number' => 'X-3']))->assertForbidden();
        $this->assertSame(1, MemberPolicy::count());
    }

    /** @test */
    public function both_patient_pages_show_the_insurance_form_and_status(): void
    {
        MemberPolicy::create(['patient_id' => $this->patient->id, 'insurer_id' => $this->insurer->id, 'member_number' => 'JUB-9', 'status' => 'pending_review', 'submitted_by' => 'patient']);
        $url = URL::temporarySignedRoute('patient.visits', now()->addHour(), ['patient' => $this->patient->id]);

        $this->get($url)->assertOk()->assertSee('Send my insurance details')->assertSee('Being checked')->assertSee('Jubilee');

        $this->withSession(['patient_auth' => ['phone' => '+256772123456', 'patient_ids' => [$this->patient->id]]])
            ->get('/patient/records')->assertOk()->assertSee('Send insurance details')->assertSee('Being checked');
    }
}
