<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\HealthFacility;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PrescriptionDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private HealthFacility $clinic;
    private HealthFacility $otherClinic;
    private Doctor $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clinic = $this->facility();
        $this->otherClinic = $this->facility();
        $this->doctor = Doctor::create([
            'name' => 'Rx Doctor', 'email' => 'rx@test.com', 'specialization' => 'General Practitioner',
            'contact' => '256722222222', 'meeting_slug' => 'rx-doc',
        ]);
        $this->patient = $this->patient($this->clinic);
    }

    private function facility(): HealthFacility
    {
        return HealthFacility::create([
            'name' => 'C' . uniqid(), 'email' => uniqid() . '@test.com', 'contact_number' => '256711111111',
            'contact' => '256711111111', 'location' => 'Kampala', 'type' => 'clinic',
        ]);
    }

    private function patient(HealthFacility $f): Patient
    {
        return Patient::create([
            'patient_id' => 'P' . random_int(100000, 999999), 'name' => 'Pat' . uniqid(), 'gender' => 'female',
            'birth_date' => '1990-01-01', 'health_facility_id' => $f->id, 'contact_number' => '0772123456',
        ]);
    }

    private function as(string $type, $model)
    {
        return $this->withSession(['authenticated_user' => ['type' => $type, 'id' => $model->id, 'name' => 'x', 'email' => 'x@t.com']]);
    }

    private function appointment(): Appointment
    {
        $d = Duration::firstOrCreate(['minutes' => 30, 'duration_type' => 'general'], ['price' => 50000, 'is_active' => true]);

        return Appointment::create([
            'health_facility_id' => $this->clinic->id, 'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'duration_id' => $d->id, 'appointment_time' => now()->addDay(), 'reason' => 'Cough', 'status' => 'confirmed',
        ]);
    }

    private function rx(array $over = []): Prescription
    {
        return Prescription::create($over + ['patient_id' => $this->patient->id, 'source' => 'issued', 'status' => 'issued']);
    }

    private function sms(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 201)]);
        config(['services.africastalking.username' => 'u', 'services.africastalking.api_key' => 'k']);
    }

    /** @test */
    public function only_the_appointments_doctor_can_issue_a_prescription(): void
    {
        $a = $this->appointment();
        $body = ['items' => [['name' => 'Amoxicillin', 'dosage' => '500mg', 'quantity' => 21, 'instructions' => '3 times daily']]];

        $this->as('doctor', $this->doctor)->postJson("/care/appointments/{$a->id}/prescriptions", $body)->assertCreated();
        $this->assertDatabaseHas('prescription_items', ['name' => 'Amoxicillin', 'quantity' => 21]);
        $this->assertDatabaseHas('prescriptions', ['appointment_id' => $a->id, 'status' => 'issued', 'source' => 'issued']);

        $other = Doctor::create(['name' => 'Other', 'email' => 'o@test.com', 'specialization' => 'GP', 'contact' => '1', 'meeting_slug' => 'o-doc']);
        $this->as('doctor', $other)->postJson("/care/appointments/{$a->id}/prescriptions", $body)->assertForbidden();
        $this->as('health_facility', $this->clinic)->postJson("/care/appointments/{$a->id}/prescriptions", $body)->assertForbidden();
        $this->as('doctor', $this->doctor)->postJson("/care/appointments/{$a->id}/prescriptions", ['items' => []])->assertStatus(422);
    }

    /** @test */
    public function patient_uploads_a_photo_through_a_signed_link_only(): void
    {
        Storage::fake('local');
        $url = URL::temporarySignedRoute('patient.prescriptions.upload', now()->addHour(), ['patient' => $this->patient->id]);

        $this->post("/my-visits/{$this->patient->id}/prescriptions", ['photo' => UploadedFile::fake()->image('rx.jpg')])->assertForbidden();
        $this->post($url, ['photo' => UploadedFile::fake()->create('virus.exe', 10)])->assertSessionHasErrors('photo');

        $this->post($url, ['photo' => UploadedFile::fake()->image('rx.jpg'), 'notes' => 'from Dr X'])->assertRedirect();

        $rx = Prescription::firstOrFail();
        $this->assertSame('pending_review', $rx->status);
        $this->assertSame('uploaded', $rx->source);
        Storage::disk('local')->assertExists($rx->image_path);
    }

    /** @test */
    public function review_queue_and_photo_are_limited_to_the_patients_own_clinic(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('prescriptions/x.jpg', 'img');
        $mine = $this->rx(['source' => 'uploaded', 'status' => 'pending_review', 'image_path' => 'prescriptions/x.jpg']);
        $theirs = Prescription::create(['patient_id' => $this->patient($this->otherClinic)->id, 'source' => 'uploaded', 'status' => 'pending_review']);

        $ids = collect($this->as('health_facility', $this->clinic)->getJson('/care/prescriptions')->assertOk()->json())->pluck('id');
        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($theirs->id));

        $this->as('health_facility', $this->clinic)->get("/care/prescriptions/{$mine->id}/image")->assertOk();
        $this->as('health_facility', $this->otherClinic)->get("/care/prescriptions/{$mine->id}/image")->assertNotFound();
        $this->flushSession();
        $this->assertNotSame('img', $this->get("/care/prescriptions/{$mine->id}/image")->getContent());
    }

    /** @test */
    public function reviewing_approves_or_rejects_once_and_texts_the_patient(): void
    {
        $this->sms();
        $rx = $this->rx(['source' => 'uploaded', 'status' => 'pending_review']);

        $this->as('health_facility', $this->otherClinic)->postJson("/care/prescriptions/{$rx->id}/review", ['result' => 'approved'])->assertNotFound();

        $this->as('health_facility', $this->clinic)->postJson("/care/prescriptions/{$rx->id}/review", ['result' => 'approved'])->assertOk();
        $this->assertSame('approved', $rx->fresh()->status);
        Http::assertSent(fn ($r) => str_contains($r['message'], 'approved'));

        $this->as('health_facility', $this->clinic)->postJson("/care/prescriptions/{$rx->id}/review", ['result' => 'rejected'])->assertStatus(409);
    }

    /** @test */
    public function delivery_can_only_be_requested_for_a_usable_prescription_by_its_own_patient(): void
    {
        $pending = $this->rx(['source' => 'uploaded', 'status' => 'pending_review']);
        $unpaid = $this->rx(['total_amount' => 20000, 'patient_amount' => 20000, 'payment_status' => 'unpaid']);
        $ok = $this->rx(['total_amount' => 20000, 'patient_amount' => 20000, 'payment_status' => 'paid']);
        $link = fn (Prescription $p, Patient $pt) => URL::temporarySignedRoute('patient.prescriptions.delivery', now()->addHour(), ['patient' => $pt->id, 'prescription' => $p->id]);

        $this->post($link($pending, $this->patient), ['address' => 'Ntinda'])->assertRedirect();
        $this->assertNull($pending->fresh()->delivery_status);

        $this->post($link($unpaid, $this->patient), ['address' => 'Ntinda'])->assertRedirect();      // medicine must be paid for first
        $this->assertNull($unpaid->fresh()->delivery_status);

        $stranger = $this->patient($this->otherClinic);
        $this->post($link($ok, $stranger), ['address' => 'Ntinda'])->assertNotFound();

        $this->post($link($ok, $this->patient), ['address' => 'Plot 4, Ntinda'])->assertRedirect();
        $ok->refresh();
        $this->assertSame('requested', $ok->delivery_status);
        $this->assertSame('Plot 4, Ntinda', $ok->delivery_address);
        $this->assertSame('0772123456', $ok->delivery_phone);
    }

    /** @test */
    public function staff_move_delivery_forward_only_and_the_patient_is_texted(): void
    {
        $this->sms();
        $rx = $this->rx(['delivery_status' => 'requested']);
        $go = fn ($status) => $this->as('health_facility', $this->clinic)->postJson("/care/prescriptions/{$rx->id}/delivery", ['status' => $status]);

        $go('delivered')->assertStatus(409);            // cannot skip steps
        $go('preparing')->assertOk();
        $go('out_for_delivery')->assertOk();
        Http::assertSent(fn ($r) => str_contains($r['message'], 'on its way'));
        $go('delivered')->assertOk();
        $go('cancelled')->assertStatus(409);           // finished orders are closed
        $this->assertSame('delivered', $rx->fresh()->delivery_status);
    }

    /** @test */
    public function my_visits_shows_prescriptions_and_the_forms(): void
    {
        $this->rx(['total_amount' => 5000, 'patient_amount' => 5000, 'payment_status' => 'paid'])->items()->create(['name' => 'Paracetamol', 'dosage' => '500mg', 'quantity' => 10]);
        $url = URL::temporarySignedRoute('patient.visits', now()->addHour(), ['patient' => $this->patient->id]);

        $this->get($url)->assertOk()->assertSee('Paracetamol')->assertSee('Request delivery')->assertSee('Send for review');
    }
}
