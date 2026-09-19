<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\HealthFacility;
use App\Models\Patient;
use App\Services\AppointmentPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentWebhookTokenTest extends TestCase
{
    use RefreshDatabase;

    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['status' => 'success', 'data' => ['transaction' => ['uuid' => 'tok-1']]], 200)]);
        $f = HealthFacility::create(['name' => 'C', 'email' => 'c@t.com', 'contact_number' => '1', 'contact' => '1', 'location' => 'K', 'type' => 'clinic']);
        $d = Doctor::create(['name' => 'D', 'email' => 'd@t.com', 'specialization' => 'GP', 'contact' => '1', 'meeting_slug' => 'd']);
        $du = Duration::create(['minutes' => 30, 'duration_type' => 'general', 'price' => 50000, 'is_active' => true]);
        $p = Patient::create(['patient_id' => 'P1', 'name' => 'Pat', 'gender' => 'female', 'birth_date' => '1990-01-01', 'health_facility_id' => $f->id, 'contact_number' => '0772123456']);
        $this->appointment = Appointment::create([
            'health_facility_id' => $f->id, 'patient_id' => $p->id, 'doctor_id' => $d->id, 'duration_id' => $du->id,
            'appointment_time' => now()->addDay(), 'reason' => 'x', 'status' => 'awaiting_payment', 'payment_reference' => 'tok-1', 'coverage_type' => 'self_pay',
        ]);
    }

    private function completed(string $query = '')
    {
        return $this->postJson('/marzpay/webhook' . $query, ['event_type' => 'collection.completed', 'transaction' => ['uuid' => 'tok-1', 'reference' => 'r', 'status' => 'completed', 'amount' => 50000]]);
    }

    /** @test */
    public function with_a_token_configured_forged_confirmations_are_rejected(): void
    {
        config(['services.marzpay.webhook_token' => 's3cret-token']);

        $this->completed()->assertStatus(401);
        $this->completed('?token=wrong')->assertStatus(401);
        $this->assertSame('awaiting_payment', $this->appointment->fresh()->status);

        $this->completed('?token=s3cret-token')->assertOk();
        $this->assertSame('confirmed', $this->appointment->fresh()->status);
    }

    /** @test */
    public function the_callback_url_we_give_the_provider_carries_the_token(): void
    {
        config(['services.marzpay.webhook_token' => 's3cret-token']);

        app(AppointmentPayments::class)->requestMobileMoney($this->appointment, '0701555666');

        Http::assertSent(fn ($r) => str_contains($r['callback_url'] ?? '', 'token=s3cret-token'));
    }

    /** @test */
    public function without_a_token_configured_the_webhook_behaves_as_before(): void
    {
        config(['services.marzpay.webhook_token' => null]);

        $this->completed()->assertOk();
        $this->assertSame('confirmed', $this->appointment->fresh()->status);

        Http::fake(['*' => Http::response(['status' => 'success', 'data' => ['transaction' => ['uuid' => 'tok-2']]], 200)]);
        app(AppointmentPayments::class)->requestMobileMoney($this->appointment, '0701555666');
        Http::assertSent(fn ($r) => !str_contains($r['callback_url'] ?? '', 'token='));
    }
}
