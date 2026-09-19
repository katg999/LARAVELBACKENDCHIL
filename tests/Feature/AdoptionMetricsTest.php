<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consent;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\HealthFacility;
use App\Models\Patient;
use App\Services\AdoptionMetrics;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdoptionMetricsTest extends TestCase
{
    use RefreshDatabase;

    private HealthFacility $clinic;
    private HealthFacility $other;
    private Doctor $doctor;
    private Duration $duration;
    private array $appt = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-12-31 23:59:59');
        $this->clinic = $this->facility();
        $this->other = $this->facility();
        $this->doctor = Doctor::create(['name' => 'M Doctor', 'email' => 'm@test.com', 'specialization' => 'GP', 'contact' => '1', 'meeting_slug' => 'm-doc']);
        $this->duration = Duration::create(['minutes' => 30, 'duration_type' => 'general', 'price' => 50000, 'is_active' => true]);
        $this->scenario();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function facility(): HealthFacility
    {
        return HealthFacility::create(['name' => 'C' . uniqid(), 'email' => uniqid() . '@t.com', 'contact_number' => '1', 'contact' => '1', 'location' => 'K', 'type' => 'clinic']);
    }

    private function make(HealthFacility $f, Patient $p, string $created, string $when, string $status, ?string $payStatus, ?string $method, string $coverage = 'self_pay'): Appointment
    {
        $a = new Appointment([
            'health_facility_id' => $f->id, 'patient_id' => $p->id, 'doctor_id' => $this->doctor->id, 'duration_id' => $this->duration->id,
            'appointment_time' => $when, 'reason' => 'x', 'status' => $status, 'payment_status' => $payStatus, 'payment_method' => $method, 'coverage_type' => $coverage,
        ]);
        $a->forceFill(['created_at' => $created, 'updated_at' => $created])->save();

        return $a;
    }

    private function patient(HealthFacility $f, string $n): Patient
    {
        return Patient::create(['patient_id' => 'P' . random_int(100000, 999999), 'name' => $n, 'gender' => 'female', 'birth_date' => '1990-01-01', 'health_facility_id' => $f->id]);
    }

    private function scenario(): void
    {
        [$p1, $p2, $p3, $p4] = array_map(fn ($n) => $this->patient($this->clinic, $n), ['P1', 'P2', 'P3', 'P4']);
        $this->make($this->clinic, $p1, '2026-11-01', '2026-11-03', 'completed', 'completed', 'mobile_money');
        $this->make($this->clinic, $p1, '2026-11-10', '2026-11-20', 'confirmed', 'completed', 'mobile_money');   // came back within 30 days
        $this->make($this->clinic, $p2, '2026-10-01', '2026-10-02', 'completed', 'insurance', 'insurance', 'insurance');
        $this->appt['p3'] = $this->make($this->clinic, $p3, '2026-12-05', '2026-12-06', 'confirmed', 'completed', 'mobile_money');
        $this->make($this->clinic, $p4, '2026-12-10', '2026-12-12', 'completed', 'employer', 'employer', 'employer');
        $this->make($this->clinic, $p4, '2026-12-11', '2026-12-13', 'cancelled', null, null);                      // ignored
        $this->make($this->other, $this->patient($this->other, 'X'), '2026-12-06', '2026-12-07', 'completed', 'completed', 'wallet');   // another clinic
    }

    private function metrics(?HealthFacility $f = null): array
    {
        $scope = $f ? Appointment::where('health_facility_id', $f->id) : Appointment::query();

        return app(AdoptionMetrics::class)->compute($scope, Carbon::parse('2026-12-01')->startOfDay(), Carbon::parse('2026-12-31')->endOfDay());
    }

    /** @test */
    public function it_measures_paid_bookings_first_visits_time_to_consult_and_return_rates(): void
    {
        $m = $this->metrics($this->clinic);

        $this->assertSame(2, $m['bookings']);                                     // P3 and P4 (cancelled one ignored)
        $this->assertSame(1.0, $m['booked_and_paid']['share']);
        $this->assertSame(['mobile_money' => 1, 'employer' => 1], $m['payment_methods']);
        $this->assertSame(0.0, $m['insured_share']);
        $this->assertSame(2, $m['new_patients']);
        $this->assertSame(0.5, $m['first_visit_completion']['share']);            // only P4 completed
        $this->assertSame(2880.0, $m['minutes_booking_to_consult']['median']);    // P4: 2 days
        $this->assertSame(['eligible' => 2, 'returned' => 1, 'share' => 0.5], $m['repeat_30_days']);   // P1 returned, P2 did not
        $this->assertSame(['eligible' => 1, 'returned' => 0, 'share' => 0.0], $m['repeat_90_days']);
        $this->assertNull($m['call_load']);
    }

    /** @test */
    public function joining_a_visit_counts_as_attending_it(): void
    {
        Consent::create(['patient_id' => $this->appt['p3']->patient_id, 'appointment_id' => $this->appt['p3']->id, 'type' => 'video_visit', 'granted_at' => now()]);

        $this->assertSame(1.0, $this->metrics($this->clinic)['first_visit_completion']['share']);
    }

    /** @test */
    public function a_clinics_numbers_exclude_other_clinics_and_the_all_view_includes_them(): void
    {
        $this->assertSame(2, $this->metrics($this->clinic)['bookings']);
        $this->assertSame(3, $this->metrics()['bookings']);
        $this->assertSame(1, $this->metrics($this->other)['bookings']);
    }

    /** @test */
    public function empty_periods_give_no_data_not_errors(): void
    {
        $m = app(AdoptionMetrics::class)->compute(Appointment::where('health_facility_id', 0), now()->subDays(30), now());

        $this->assertSame(0, $m['bookings']);
        $this->assertNull($m['booked_and_paid']['share']);
        $this->assertNull($m['minutes_booking_to_consult']['median']);
        $this->assertNull($m['repeat_30_days']['share']);
    }

    /** @test */
    public function staff_see_their_own_numbers_and_admins_see_all(): void
    {
        $as = fn (HealthFacility $f) => $this->withSession(['authenticated_user' => ['type' => 'health_facility', 'id' => $f->id, 'name' => 'C', 'email' => 'c@t.com']]);

        $as($this->clinic)->getJson('/metrics/adoption?from=2026-12-01&to=2026-12-31')->assertOk()->assertJson(['bookings' => 2]);
        $as($this->clinic)->get('/metrics/adoption?from=2026-12-01&to=2026-12-31')->assertOk()->assertSee('completed a first visit');

        $this->withSession(['authenticated_user' => ['type' => 'school', 'id' => 1, 'name' => 'S', 'email' => 's@t.com']])
            ->getJson('/metrics/adoption')->assertForbidden();

        $this->flushSession();
        $admin = \App\User::create(['name' => 'A', 'email' => 'a@t.com', 'password' => bcrypt('x'), 'is_admin' => true]);
        $this->actingAs($admin)->getJson('/manage/metrics/adoption?from=2026-12-01&to=2026-12-31')->assertOk()->assertJson(['bookings' => 3]);
    }
}
