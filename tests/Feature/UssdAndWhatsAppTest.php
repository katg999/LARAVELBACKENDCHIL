<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\HealthFacility;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Services\PatientMessenger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UssdAndWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private Doctor $doctor;
    private Duration $duration;
    private HealthFacility $clinic;
    private bool $whatsAppAccepts = true;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(function (HttpRequest $r) {
            if (str_contains($r->url(), 'graph.facebook.com')) {
                return $this->whatsAppAccepts
                    ? Http::response(['messages' => [['id' => 'wamid.1']]], 200)
                    : Http::response(['error' => 'outside the 24 hour window'], 400);
            }
            if (str_contains($r->url(), 'africastalking')) {
                return Http::response(['ok' => true], 201);
            }

            return Http::response(['status' => 'success', 'data' => ['transaction' => ['uuid' => 'tx-ussd']]], 200);
        });
        config([
            'services.ussd.secret' => 's3cret',
            'services.africastalking.username' => 'u', 'services.africastalking.api_key' => 'k',
        ]);
        $this->clinic = HealthFacility::create(['name' => 'C', 'email' => 'c@t.com', 'contact_number' => '1', 'contact' => '1', 'location' => 'K', 'type' => 'clinic']);
        $this->doctor = Doctor::create(['name' => 'Ussd Doctor', 'email' => 'u@t.com', 'specialization' => 'GP', 'contact' => '1', 'meeting_slug' => 'u-doc']);
        $this->duration = Duration::create(['minutes' => 30, 'duration_type' => 'general', 'price' => 50000, 'is_active' => true]);
        $this->patient = Patient::create(['patient_id' => 'P1', 'name' => 'Grace', 'gender' => 'female', 'birth_date' => '1990-01-01', 'health_facility_id' => $this->clinic->id, 'contact_number' => '0772123456']);
    }

    private function appointment(string $status = 'awaiting_payment', string $when = '+1 day'): Appointment
    {
        return Appointment::create([
            'health_facility_id' => $this->clinic->id, 'patient_id' => $this->patient->id, 'doctor_id' => $this->doctor->id,
            'duration_id' => $this->duration->id, 'appointment_time' => now()->modify($when), 'reason' => 'x', 'status' => $status, 'coverage_type' => 'self_pay',
        ]);
    }

    private function ussd(string $text, string $phone = '+256772123456', string $token = 's3cret')
    {
        return $this->post('/ussd?token=' . $token, ['sessionId' => 's1', 'serviceCode' => '*123#', 'phoneNumber' => $phone, 'text' => $text]);
    }

    // ---- USSD ----

    /** @test */
    public function the_callback_needs_the_shared_secret(): void
    {
        $this->ussd('', token: 'wrong')->assertForbidden();
        $this->post('/ussd', ['phoneNumber' => '+256772123456', 'text' => ''])->assertForbidden();

        config(['services.ussd.secret' => null]);
        $this->ussd('', token: '')->assertForbidden();           // no secret configured means closed, not open
    }

    /** @test */
    public function unknown_numbers_are_told_and_known_numbers_get_the_menu(): void
    {
        $this->assertStringStartsWith('END This number is not registered', $this->ussd('', '+256700999888')->getContent());

        $menu = $this->ussd('')->assertOk()->getContent();
        $this->assertStringStartsWith('CON ', $menu);
        $this->assertStringContainsString('1 My visits', $menu);
    }

    /** @test */
    public function my_visits_lists_upcoming_visits(): void
    {
        $this->assertSame('END You have no upcoming visits.', $this->ussd('1')->getContent());

        $this->appointment('confirmed');
        $text = $this->ussd('1')->getContent();
        $this->assertStringStartsWith('END 1. ', $text);
        $this->assertStringContainsString('Dr Ussd Doctor', $text);
        $this->assertStringContainsString('confirmed', $text);
    }

    /** @test */
    public function paying_walks_through_choose_confirm_and_charges_the_callers_own_number(): void
    {
        $this->assertSame('END You have nothing to pay right now.', $this->ussd('2')->getContent());

        $a = $this->appointment();

        $this->assertStringContainsString('UGX 50,000', $this->ussd('2')->getContent());
        $this->assertStringContainsString('Pay UGX 50,000 from +256772123456?', $this->ussd('2*1')->getContent());

        $this->assertStringStartsWith('END Cancelled', $this->ussd('2*1*2')->getContent());
        $this->assertSame(0, Payment::count());

        $this->assertStringStartsWith('END Payment request sent', $this->ussd('2*1*1')->getContent());
        $payment = Payment::firstOrFail();
        $this->assertSame($a->id, $payment->appointment_id);
        $this->assertSame('+256772123456', $payment->phone_number);
        $this->assertSame('END Invalid choice.', $this->ussd('2*9')->getContent());
    }

    /** @test */
    public function medicine_shows_delivery_status_and_link_option_texts_a_signed_link(): void
    {
        $this->assertSame('END You have no medicine orders.', $this->ussd('3')->getContent());

        Prescription::create(['patient_id' => $this->patient->id, 'source' => 'issued', 'status' => 'issued', 'delivery_status' => 'out_for_delivery'])
            ->items()->create(['name' => 'Coartem']);
        $this->assertSame('END Coartem: out for delivery', $this->ussd('3')->getContent());

        $this->assertSame('END We sent a link to your phone.', $this->ussd('4')->getContent());
        Http::assertSent(fn (HttpRequest $r) => str_contains($r['message'] ?? '', '/my-visits/' . $this->patient->id) && str_contains($r['message'], 'signature='));
        $this->assertSame('END Invalid choice.', $this->ussd('9')->getContent());
    }

    // ---- WhatsApp ----

    private function waConfig(): void
    {
        config(['services.whatsapp.token' => 'wa-token', 'services.whatsapp.phone_number_id' => '123', 'services.whatsapp.verify_token' => 'vt', 'services.whatsapp.app_secret' => 'appsecret']);
    }

    private function inbound(string $from, ?string $sig = null)
    {
        $payload = json_encode(['entry' => [['changes' => [['value' => ['messages' => [['from' => $from, 'text' => ['body' => 'hi']]]]]]]]]);
        $sig ??= 'sha256=' . hash_hmac('sha256', $payload, 'appsecret');

        return $this->call('POST', '/whatsapp/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $sig], $payload);
    }

    /** @test */
    public function metas_verification_handshake_needs_the_verify_token(): void
    {
        $this->waConfig();

        $this->get('/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=vt&hub_challenge=abc123')->assertOk()->assertSee('abc123');
        $this->get('/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=nope&hub_challenge=abc123')->assertForbidden();
    }

    /** @test */
    public function inbound_messages_must_be_signed_and_get_the_visits_link_back(): void
    {
        $this->waConfig();

        $this->inbound('256772123456', 'sha256=forged')->assertForbidden();
        Http::assertNothingSent();

        $this->inbound('256772123456')->assertOk();
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'graph.facebook.com') && $r['to'] === '256772123456'
            && str_contains($r['text']['body'], '/my-visits/' . $this->patient->id) && $r->hasHeader('Authorization', 'Bearer wa-token'));

        $this->inbound('256700999888')->assertOk();
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'graph.facebook.com') && str_contains($r['text']['body'] ?? '', 'not registered'));
    }

    /** @test */
    public function without_an_app_secret_the_inbound_webhook_stays_closed(): void
    {
        config(['services.whatsapp.app_secret' => null]);
        $this->inbound('256772123456', 'sha256=anything')->assertForbidden();
    }

    private function requestsTo(string $needle): int
    {
        return Http::recorded()->filter(fn ($pair) => str_contains($pair[0]->url(), $needle))->count();
    }

    /** @test */
    public function the_messenger_follows_the_channel_setting_and_falls_back_to_sms(): void
    {
        $this->waConfig();
        $messenger = app(PatientMessenger::class);

        config(['services.notifications.channel' => 'sms']);
        $messenger->send('0772123456', 'one');
        $this->assertSame([0, 1], [$this->requestsTo('graph.facebook'), $this->requestsTo('africastalking')]);

        config(['services.notifications.channel' => 'whatsapp']);
        $messenger->send('0772123456', 'two');
        $this->assertSame([1, 1], [$this->requestsTo('graph.facebook'), $this->requestsTo('africastalking')]);   // whatsapp worked, no extra sms

        $this->whatsAppAccepts = false;
        $this->assertTrue($messenger->send('0772123456', 'three'));                                     // refused by Meta, delivered by SMS
        $this->assertSame(2, $this->requestsTo('africastalking'));     // running total: one, then the fallback

        config(['services.notifications.channel' => 'both']);
        $this->whatsAppAccepts = true;
        $messenger->send('0772123456', 'four');
        $this->assertSame([3, 3], [$this->requestsTo('graph.facebook'), $this->requestsTo('africastalking')]);   // both channels used
    }
}
