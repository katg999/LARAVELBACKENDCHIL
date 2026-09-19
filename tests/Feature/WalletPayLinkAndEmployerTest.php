<?php

namespace Tests\Feature;

use App\Mail\DoctorAppointmentConfirmationMail;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\Employer;
use App\Models\HealthFacility;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class WalletPayLinkAndEmployerTest extends TestCase
{
    use RefreshDatabase;

    private HealthFacility $clinic;
    private HealthFacility $otherClinic;
    private Doctor $doctor;
    private Duration $duration;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Http::fake(['*' => Http::response(['status' => 'success', 'data' => ['transaction' => ['uuid' => 'tx-1']]], 200)]);
        config(['services.africastalking.username' => 'u', 'services.africastalking.api_key' => 'k']);
        $this->clinic = $this->facility();
        $this->otherClinic = $this->facility();
        $this->doctor = Doctor::create([
            'name' => 'Pay Doctor', 'email' => 'paydoc@test.com', 'specialization' => 'General Practitioner',
            'contact' => '256722222222', 'meeting_slug' => 'pay-doc',
        ]);
        $this->duration = Duration::create(['minutes' => 30, 'duration_type' => 'general', 'price' => 50000, 'is_active' => true]);
        $this->patient = $this->patient($this->clinic);
    }

    private function facility(): HealthFacility
    {
        return HealthFacility::create([
            'name' => 'C' . uniqid(), 'email' => uniqid() . '@test.com', 'contact_number' => '256711111111',
            'contact' => '256711111111', 'location' => 'Kampala', 'type' => 'clinic',
        ]);
    }

    private function patient(HealthFacility $f, string $name = 'Pat'): Patient
    {
        return Patient::create([
            'patient_id' => 'P' . random_int(100000, 999999), 'name' => $name . uniqid(), 'gender' => 'female',
            'birth_date' => '1990-01-01', 'health_facility_id' => $f->id, 'contact_number' => '0772123456',
        ]);
    }

    private function unpaid(?Patient $p = null, $time = null): Appointment
    {
        $p ??= $this->patient;

        return Appointment::create([
            'health_facility_id' => $this->clinic->id, 'patient_id' => $p->id, 'doctor_id' => $this->doctor->id,
            'duration_id' => $this->duration->id, 'appointment_time' => $time ?? now()->addDay(), 'reason' => 'Check',
            'status' => 'awaiting_payment', 'coverage_type' => 'self_pay',
        ]);
    }

    private function signed(string $route, Appointment $a, int $minutes = 60): string
    {
        return URL::temporarySignedRoute($route, now()->addMinutes($minutes), ['appointment' => $a->id]);
    }

    private function asClinic(HealthFacility $f)
    {
        return $this->withSession(['authenticated_user' => ['type' => 'health_facility', 'id' => $f->id, 'name' => 'C', 'email' => 'c@t.com']]);
    }

    private function admin()
    {
        return \App\User::create(['name' => 'Admin', 'email' => uniqid() . '@test.com', 'password' => bcrypt('x'), 'is_admin' => true]);
    }

    // ---- wallet ----

    /** @test */
    public function staff_top_up_a_wallet_only_for_their_own_patients(): void
    {
        $theirs = $this->patient($this->otherClinic);

        $this->asClinic($this->clinic)->postJson("/care/patients/{$this->patient->id}/wallet/credit", ['amount' => 70000])
            ->assertOk()->assertJson(['balance' => '70000.00']);
        $this->asClinic($this->clinic)->postJson("/care/patients/{$theirs->id}/wallet/credit", ['amount' => 1000])->assertNotFound();
        $this->asClinic($this->clinic)->postJson("/care/patients/{$this->patient->id}/wallet/credit", ['amount' => -5])->assertStatus(422);

        $this->assertDatabaseHas('wallet_transactions', ['type' => 'credit', 'amount' => 70000, 'balance_after' => 70000]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'wallet.credited', 'subject_id' => $this->patient->id]);
    }

    /** @test */
    public function paying_from_the_wallet_confirms_the_visit_once_and_never_overspends(): void
    {
        $a = $this->unpaid();
        Wallet::forPatient($this->patient)->credit(30000);

        $this->post($this->signed('visit.pay.wallet', $a))->assertRedirect();
        $this->assertSame('awaiting_payment', $a->fresh()->status);                    // 30,000 < 50,000
        $this->assertSame('30000.00', Wallet::forPatient($this->patient)->balance);

        Wallet::forPatient($this->patient)->credit(30000);
        $this->post($this->signed('visit.pay.wallet', $a))->assertRedirect();

        $a->refresh();
        $this->assertSame('confirmed', $a->status);
        $this->assertSame('wallet', $a->payment_method);
        $this->assertSame('10000.00', Wallet::forPatient($this->patient)->balance);
        $this->assertDatabaseHas('wallet_transactions', ['type' => 'debit', 'amount' => 50000, 'appointment_id' => $a->id]);
        Mail::assertSent(DoctorAppointmentConfirmationMail::class);
        Http::assertSent(fn ($r) => str_contains($r['message'] ?? '', '/visit/' . $a->id));

        // paying again does nothing: not payable any more, balance untouched
        $this->post($this->signed('visit.pay.wallet', $a))->assertRedirect();
        $this->assertSame('10000.00', Wallet::forPatient($this->patient)->balance);

        $this->post("/visit/{$a->id}/pay/wallet")->assertForbidden();                  // unsigned
    }

    /** @test */
    public function the_visit_page_offers_the_right_payment_options(): void
    {
        $a = $this->unpaid();
        $url = $this->signed('visit.show', $a);

        $this->get($url)->assertOk()->assertSee('UGX 50,000')->assertSee('Pay with mobile money')->assertDontSee('Pay from wallet');

        Wallet::forPatient($this->patient)->credit(60000);
        $this->get($url)->assertSee('Pay from wallet');
    }

    /** @test */
    public function a_relative_can_pay_with_their_own_mobile_money_number_from_the_link(): void
    {
        $a = $this->unpaid();

        $this->post($this->signed('visit.pay.momo', $a), ['phone' => '0701 555 666'])->assertRedirect();

        $payment = Payment::firstOrFail();
        $this->assertSame('+256701555666', $payment->phone_number);
        $this->assertEquals(50000, $payment->amount);
        $this->assertSame('mobile_money', $a->fresh()->payment_method);
        $this->assertSame('pending', $a->fresh()->payment_status);

        $this->post($this->signed('visit.pay.momo', $a), [])->assertSessionHasErrors('phone');
    }

    /** @test */
    public function staff_can_text_the_pay_link_to_someone_else_for_their_own_appointments_only(): void
    {
        $a = $this->unpaid();
        $other = Appointment::create([
            'health_facility_id' => $this->otherClinic->id, 'patient_id' => $this->patient($this->otherClinic)->id, 'doctor_id' => $this->doctor->id,
            'duration_id' => $this->duration->id, 'appointment_time' => now()->addDay(), 'reason' => 'x', 'status' => 'awaiting_payment', 'coverage_type' => 'self_pay',
        ]);

        $this->asClinic($this->clinic)->postJson("/care/appointments/{$a->id}/pay-link", ['phone' => '0701555666'])->assertOk();
        Http::assertSent(fn ($r) => ($r['to'] ?? null) === '+256701555666' && str_contains($r['message'], '/visit/' . $a->id));

        $this->asClinic($this->clinic)->postJson("/care/appointments/{$other->id}/pay-link", ['phone' => '0701555666'])->assertNotFound();
    }

    // ---- employers ----

    /** @test */
    public function only_admins_manage_employers(): void
    {
        $this->post('/manage/employers', ['name' => 'Acme'])->assertRedirect(route('login'));
        $this->asClinic($this->clinic)->post('/manage/employers', ['name' => 'Acme'])->assertRedirect(route('login'));
        $this->assertSame(0, Employer::count());

        $admin = $this->admin();
        $e = $this->actingAs($admin)->postJson('/manage/employers', ['name' => 'Acme Ltd', 'monthly_cap' => 100000])->assertCreated()->json();
        $this->actingAs($admin)->postJson("/manage/employers/{$e['id']}/members", ['patient_id' => $this->patient->id])->assertOk()->assertJson(['members' => 1]);
    }

    private function employerBooking(array $over = [])
    {
        return $this->postJson('/appointments', $over + [
            'doctor_id' => $this->doctor->id, 'duration_id' => $this->duration->id,
            'appointment_time' => now()->addDays(2)->setTime(9, 0)->toDateTimeString(),
            'reason' => 'Checkup', 'patient_id' => $this->patient->id, 'health_facility_id' => $this->clinic->id,
        ]);
    }

    /** @test */
    public function an_employer_booking_is_confirmed_at_once_and_everyone_is_told(): void
    {
        $emp = Employer::create(['name' => 'Acme', 'monthly_cap' => 100000]);
        $emp->members()->attach($this->patient->id);

        $this->employerBooking(['employer_id' => $emp->id]);

        $this->assertDatabaseHas('appointments', ['patient_id' => $this->patient->id, 'status' => 'confirmed', 'coverage_type' => 'employer', 'payment_method' => 'employer', 'employer_id' => $emp->id]);
        Mail::assertSent(DoctorAppointmentConfirmationMail::class);
        Http::assertSent(fn ($r) => str_contains($r['message'] ?? '', '/visit/'));
    }

    /** @test */
    public function employer_bookings_are_refused_for_non_members_double_coverage_and_over_the_limit(): void
    {
        $emp = Employer::create(['name' => 'Acme', 'monthly_cap' => 60000]);

        $this->employerBooking(['employer_id' => $emp->id])->assertStatus(422);            // not a member

        $emp->members()->attach($this->patient->id);
        $policy = \App\Models\MemberPolicy::create(['patient_id' => $this->patient->id, 'insurer_id' => \App\Models\Insurer::create(['name' => 'J', 'code' => 'j'])->id, 'member_number' => 'M1']);
        $this->employerBooking(['employer_id' => $emp->id, 'member_policy_id' => $policy->id])->assertStatus(422);   // both

        $this->employerBooking(['employer_id' => $emp->id])->assertSuccessful();            // 50,000 of 60,000
        $this->employerBooking(['employer_id' => $emp->id, 'appointment_time' => now()->addDays(2)->setTime(11, 0)->toDateTimeString()])->assertStatus(422); // would be 100,000

        $emp->update(['active' => false]);
        $this->employerBooking(['employer_id' => $emp->id, 'appointment_time' => now()->addDays(2)->setTime(13, 0)->toDateTimeString()])->assertStatus(422);
    }

    /** @test */
    public function the_monthly_invoice_lists_the_months_visits_with_a_total(): void
    {
        $emp = Employer::create(['name' => 'Acme Ltd']);
        $emp->members()->attach($this->patient->id);
        $mk = fn ($when, $status = 'confirmed') => Appointment::create([
            'health_facility_id' => $this->clinic->id, 'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'duration_id' => $this->duration->id, 'appointment_time' => $when, 'reason' => 'x', 'status' => $status,
            'coverage_type' => 'employer', 'employer_id' => $emp->id,
        ]);
        $mk('2026-10-05 10:00');
        $mk('2026-10-20 10:00');
        $mk('2026-10-25 10:00', 'cancelled');   // not billed
        $mk('2026-11-02 10:00');                // another month

        $csv = $this->actingAs($this->admin())->get("/manage/employers/{$emp->id}/invoice.csv?month=2026-10")->assertOk()->streamedContent();

        $this->assertStringContainsString('TOTAL,,,,,100000', str_replace('"', '', $csv));
        $this->assertSame(4, substr_count($csv, "\n"));        // header, 2 visits, total
    }
}
