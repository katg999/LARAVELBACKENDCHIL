<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\HealthFacility;
use App\Models\LabTest;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\PatientLoginCode;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PatientPortalAndConsentTest extends TestCase
{
    use RefreshDatabase;

    private HealthFacility $clinic;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['ok' => true], 201)]);
        config(['services.africastalking.username' => 'u', 'services.africastalking.api_key' => 'k']);
        RateLimiter::clear('patient-code:+256772123456');
        $this->clinic = HealthFacility::create([
            'name' => 'Clinic', 'email' => uniqid() . '@test.com', 'contact_number' => '256711111111',
            'contact' => '256711111111', 'location' => 'Kampala', 'type' => 'clinic',
        ]);
        $this->doctor = Doctor::create([
            'name' => 'Portal Doctor', 'email' => 'pd@test.com', 'specialization' => 'General Practitioner',
            'contact' => '256722222222', 'meeting_slug' => 'pd-doc',
        ]);
    }

    private function patient(string $name, ?string $contact = '0772123456', ?string $parent = null): Patient
    {
        return Patient::create([
            'patient_id' => 'P' . random_int(100000, 999999), 'name' => $name, 'gender' => 'female',
            'birth_date' => '1990-01-01', 'health_facility_id' => $this->clinic->id,
            'contact_number' => $contact, 'parent_contact' => $parent,
        ]);
    }

    private function lastCode(): ?string
    {
        $sent = Http::recorded()->map(fn ($pair) => $pair[0])->filter(fn (HttpRequest $r) => str_contains($r['message'] ?? '', 'Easemed code'))->last();

        return $sent && preg_match('/code is (\d{6})/', $sent['message'], $m) ? $m[1] : null;
    }

    private function codesSent(): int
    {
        return Http::recorded()->filter(fn ($pair) => str_contains($pair[0]['message'] ?? '', 'Easemed code'))->count();
    }

    private function login(string $phone = '0772123456')
    {
        $this->post('/patient/login/code', ['phone' => $phone]);

        return $this->post('/patient/login/verify', ['phone' => $phone, 'code' => $this->lastCode()]);
    }

    /** @test */
    public function unknown_and_known_numbers_get_the_same_reply_but_only_known_ones_get_a_code(): void
    {
        $this->patient('Grace');

        $known = $this->post('/patient/login/code', ['phone' => '0772123456']);
        $this->assertSame(1, $this->codesSent());

        $unknown = $this->post('/patient/login/code', ['phone' => '0700000000']);
        $this->assertSame(1, $this->codesSent());

        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
    }

    /** @test */
    public function correct_code_signs_the_patient_in_and_shows_their_records(): void
    {
        $p = $this->patient('Grace');
        MedicalHistory::create(['patient_id' => $p->id, 'doctor_id' => $this->doctor->id, 'content' => 'Malaria, treated', 'recorded_date' => now()]);
        $school = \App\Models\School::create(['name' => 'S', 'email' => 's@test.com', 'contact' => '256700000000']);
        LabTest::create(['school_id' => $school->id, 'patient_id' => $p->id, 'test_type' => 'Blood film', 'status' => 'completed', 'results' => 'Positive']);
        Prescription::create(['patient_id' => $p->id, 'source' => 'issued', 'status' => 'issued'])->items()->create(['name' => 'Coartem']);
        $stranger = $this->patient('Stranger', '0700111222');
        MedicalHistory::create(['patient_id' => $stranger->id, 'doctor_id' => $this->doctor->id, 'content' => 'Private note of someone else', 'recorded_date' => now()]);

        $this->login()->assertRedirect(route('patient.records'));

        $this->get('/patient/records')->assertOk()
            ->assertSee('Malaria, treated')->assertSee('Blood film')->assertSee('Coartem')
            ->assertDontSee('Private note of someone else');
        $this->assertDatabaseHas('audit_logs', ['actor_type' => 'patient', 'actor_id' => $p->id, 'action' => 'patient.records.viewed']);
    }

    /** @test */
    public function records_need_a_login(): void
    {
        $this->patient('Grace');
        $this->get('/patient/records')->assertRedirect(route('patient.login'));
    }

    /** @test */
    public function wrong_expired_and_reused_codes_do_not_work(): void
    {
        $this->patient('Grace');
        $this->post('/patient/login/code', ['phone' => '0772123456']);
        $good = $this->lastCode();
        $bad = $good === '000000' ? '111111' : '000000';

        $this->post('/patient/login/verify', ['phone' => '0772123456', 'code' => $bad])->assertSessionHasErrors('code');
        $this->get('/patient/records')->assertRedirect(route('patient.login'));

        PatientLoginCode::query()->update(['expires_at' => now()->subMinute()]);
        $this->post('/patient/login/verify', ['phone' => '0772123456', 'code' => $good])->assertSessionHasErrors('code');

        PatientLoginCode::query()->delete();
        $this->post('/patient/login/code', ['phone' => '0772123456']);
        $fresh = $this->lastCode();
        $this->post('/patient/login/verify', ['phone' => '0772123456', 'code' => $fresh])->assertRedirect(route('patient.records'));
        $this->post('/patient/logout');
        $this->post('/patient/login/verify', ['phone' => '0772123456', 'code' => $fresh])->assertSessionHasErrors('code'); // single use
    }

    /** @test */
    public function five_wrong_guesses_lock_the_code_even_for_the_right_answer(): void
    {
        $this->patient('Grace');
        $this->post('/patient/login/code', ['phone' => '0772123456']);
        $good = $this->lastCode();
        $bad = $good === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->post('/patient/login/verify', ['phone' => '0772123456', 'code' => $bad]);
        }

        $this->post('/patient/login/verify', ['phone' => '0772123456', 'code' => $good])->assertSessionHasErrors('code');
    }

    /** @test */
    public function code_requests_are_limited_per_number(): void
    {
        $this->patient('Grace');
        for ($i = 0; $i < 6; $i++) {
            $this->post('/patient/login/code', ['phone' => '0772123456']);
        }

        $this->assertSame(3, $this->codesSent());
    }

    /** @test */
    public function a_shared_family_phone_shows_each_childs_records_only_for_that_phone(): void
    {
        $this->patient('Child One', null, '0772123456');
        $this->patient('Child Two', null, '256772123456');
        $this->patient('Neighbour', '0755000000');

        $this->login();

        $this->get('/patient/records')->assertOk()->assertSee('Child One')->assertSee('Child Two')->assertDontSee('Neighbour');
    }

    private function openAppointment(string $status = 'confirmed', $time = null): Appointment
    {
        $p = $this->patient('Joiner');
        $d = Duration::firstOrCreate(['minutes' => 30, 'duration_type' => 'general'], ['price' => 50000, 'is_active' => true]);

        return Appointment::create([
            'health_facility_id' => $this->clinic->id, 'patient_id' => $p->id, 'doctor_id' => $this->doctor->id,
            'duration_id' => $d->id, 'appointment_time' => $time ?? now()->addMinutes(5), 'reason' => 'Check',
            'status' => $status, 'payment_status' => 'completed',
        ]);
    }

    /** @test */
    public function joining_records_consent_and_redirects_to_the_private_room(): void
    {
        $a = $this->openAppointment();
        $url = URL::temporarySignedRoute('visit.join', now()->addMinutes(30), ['appointment' => $a->id]);

        $this->post("/visit/{$a->id}/join")->assertForbidden();

        $this->post($url)->assertRedirect($a->meeting_url);
        $this->assertDatabaseHas('consents', ['patient_id' => $a->patient_id, 'appointment_id' => $a->id, 'type' => 'video_visit']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'visit.joined', 'subject_id' => $a->id]);

        $this->post($url)->assertRedirect($a->meeting_url);
        $this->assertSame(1, Consent::count());     // consent is recorded once per visit

        $this->post($url, ['audio_only' => 1])->assertRedirect($a->meeting_url . '#config.startWithVideoMuted=true&config.startAudioOnly=true');
    }

    /** @test */
    public function joining_outside_the_window_records_nothing(): void
    {
        $early = $this->openAppointment('confirmed', now()->addDay());
        $url = URL::temporarySignedRoute('visit.join', now()->addMinutes(30), ['appointment' => $early->id]);

        $response = $this->post($url);

        $this->assertStringNotContainsString('meet.jit.si', (string) $response->headers->get('Location'));
        $this->assertSame(0, Consent::count());
    }

    /** @test */
    public function a_configured_video_server_is_used_for_room_links(): void
    {
        config(['services.jitsi.base_url' => 'https://video.example.org/']);
        $a = $this->openAppointment();

        $this->assertSame('https://video.example.org/' . $a->meeting_room, $a->meeting_url);
    }

    /** @test */
    public function staff_actions_on_insurance_and_prescriptions_are_audited(): void
    {
        $a = $this->openAppointment();
        $rx = Prescription::create(['patient_id' => $a->patient_id, 'source' => 'uploaded', 'status' => 'pending_review']);
        $as = fn () => $this->withSession(['authenticated_user' => ['type' => 'health_facility', 'id' => $this->clinic->id, 'name' => 'C', 'email' => 'c@t.com']]);

        $as()->postJson("/care/prescriptions/{$rx->id}/review", ['result' => 'approved'])->assertOk();
        $as()->get('/insurance/visit-records.csv')->assertOk();

        $this->assertDatabaseHas('audit_logs', ['actor_type' => 'health_facility', 'actor_id' => $this->clinic->id, 'action' => 'prescription.approved', 'subject_id' => $rx->id]);
        $this->assertDatabaseHas('audit_logs', ['actor_type' => 'health_facility', 'action' => 'insurance.export']);
        $this->assertGreaterThan(0, AuditLog::count());
    }
}
