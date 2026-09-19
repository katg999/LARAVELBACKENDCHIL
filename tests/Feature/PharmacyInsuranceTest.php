<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\HealthFacility;
use App\Models\Insurer;
use App\Models\MemberPolicy;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PharmacyInsuranceTest extends TestCase
{
    use RefreshDatabase;

    private HealthFacility $clinic;
    private HealthFacility $other;
    private Patient $patient;
    private Insurer $insurer;
    private Prescription $rx;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(function ($r) {
            return str_contains($r->url(), 'africastalking')
                ? Http::response(['ok' => true], 201)
                : Http::response(['status' => 'success', 'data' => ['transaction' => ['uuid' => 'rx-tx-1']]], 200);
        });
        config(['services.africastalking.username' => 'u', 'services.africastalking.api_key' => 'k']);
        $this->clinic = $this->facility();
        $this->other = $this->facility();
        $this->patient = $this->patient($this->clinic);
        $this->insurer = Insurer::create(['name' => 'Jubilee', 'code' => 'jubilee']);
        $this->rx = $this->prescription();
    }

    private function facility(): HealthFacility
    {
        return HealthFacility::create(['name' => 'C' . uniqid(), 'email' => uniqid() . '@t.com', 'contact_number' => '1', 'contact' => '1', 'location' => 'K', 'type' => 'clinic']);
    }

    private function patient(HealthFacility $f): Patient
    {
        return Patient::create(['patient_id' => 'P' . random_int(100000, 999999), 'name' => 'Pat' . uniqid(), 'gender' => 'female', 'birth_date' => '1990-01-01', 'health_facility_id' => $f->id, 'contact_number' => '0772123456']);
    }

    private function prescription(?Patient $p = null): Prescription
    {
        $rx = Prescription::create(['patient_id' => ($p ?? $this->patient)->id, 'source' => 'issued', 'status' => 'issued']);
        $rx->items()->create(['name' => 'Amoxicillin', 'quantity' => 3]);
        $rx->items()->create(['name' => 'Paracetamol', 'quantity' => 2]);

        return $rx->fresh('items');
    }

    private function policy(string $status = 'active', ?Patient $p = null): MemberPolicy
    {
        return MemberPolicy::create(['patient_id' => ($p ?? $this->patient)->id, 'insurer_id' => $this->insurer->id, 'member_number' => 'M-' . uniqid(), 'status' => $status]);
    }

    private function as(HealthFacility $f)
    {
        return $this->withSession(['authenticated_user' => ['type' => 'health_facility', 'id' => $f->id, 'name' => 'C', 'email' => 'c@t.com']]);
    }

    private function prices(Prescription $rx, float $a = 4000, float $b = 1000): array
    {
        [$i1, $i2] = [$rx->items[0]->id, $rx->items[1]->id];

        return ['items' => [['id' => $i1, 'unit_price' => $a], ['id' => $i2, 'unit_price' => $b]]];   // 3x4000 + 2x1000 = 14000
    }

    private function link(string $route, Prescription $rx, ?Patient $p = null): string
    {
        return URL::temporarySignedRoute($route, now()->addHour(), ['patient' => ($p ?? $this->patient)->id, 'prescription' => $rx->id]);
    }

    // ---- pricing ----

    /** @test */
    public function self_pay_pricing_totals_the_items_and_tells_the_patient(): void
    {
        $this->as($this->clinic)->postJson("/care/prescriptions/{$this->rx->id}/price", ['coverage' => 'self_pay'] + $this->prices($this->rx))->assertOk();

        $rx = $this->rx->fresh();
        $this->assertEquals(14000, $rx->total_amount);
        $this->assertEquals(14000, $rx->patient_amount);
        $this->assertEquals(0, $rx->insurer_amount);
        $this->assertSame('unpaid', $rx->payment_status);
        Http::assertSent(fn ($r) => str_contains($r['message'] ?? '', 'UGX 14,000') && str_contains($r['message'], 'Pay here'));
    }

    /** @test */
    public function pricing_needs_every_item_priced_a_usable_prescription_and_the_right_clinic(): void
    {
        $missing = ['items' => [['id' => $this->rx->items[0]->id, 'unit_price' => 100]]];
        $this->as($this->clinic)->postJson("/care/prescriptions/{$this->rx->id}/price", ['coverage' => 'self_pay'] + $missing)->assertStatus(422);

        $this->as($this->other)->postJson("/care/prescriptions/{$this->rx->id}/price", ['coverage' => 'self_pay'] + $this->prices($this->rx))->assertNotFound();

        $waiting = Prescription::create(['patient_id' => $this->patient->id, 'source' => 'uploaded', 'status' => 'pending_review']);
        $this->as($this->clinic)->postJson("/care/prescriptions/{$waiting->id}/price", ['coverage' => 'self_pay', 'items' => [['id' => 1, 'unit_price' => 1]]])->assertStatus(422);

        $this->as($this->clinic)->postJson("/care/prescriptions/{$this->rx->id}/price", ['coverage' => 'self_pay'] + $this->prices($this->rx))->assertOk();
        $this->rx->refresh()->update(['payment_status' => 'pending']);
        $this->as($this->clinic)->postJson("/care/prescriptions/{$this->rx->id}/price", ['coverage' => 'self_pay'] + $this->prices($this->rx, 1, 1))->assertStatus(422);   // no repricing once payment started
    }

    /** @test */
    public function insurance_pricing_needs_an_active_policy_belonging_to_the_patient(): void
    {
        $body = fn ($policyId) => ['coverage' => 'insurance', 'member_policy_id' => $policyId] + $this->prices($this->rx);
        $strangers = $this->policy('active', $this->patient($this->clinic));

        $this->as($this->clinic)->postJson("/care/prescriptions/{$this->rx->id}/price", $body($this->policy('pending_review')->id))->assertStatus(422);
        $this->as($this->clinic)->postJson("/care/prescriptions/{$this->rx->id}/price", $body($strangers->id))->assertStatus(422);
        $this->as($this->clinic)->postJson("/care/prescriptions/{$this->rx->id}/price", $body(null))->assertStatus(422);

        $this->as($this->clinic)->postJson("/care/prescriptions/{$this->rx->id}/price", $body($this->policy()->id))->assertOk();
        $rx = $this->rx->fresh();
        $this->assertSame('pending', $rx->insurance_status);
        $this->assertNull($rx->patient_amount);                  // not known until the insurer answers
        $this->assertNull($rx->payment_status);
    }

    // ---- insurer decision ----

    private function pendingInsured(): Prescription
    {
        $this->as($this->clinic)->postJson("/care/prescriptions/{$this->rx->id}/price", ['coverage' => 'insurance', 'member_policy_id' => $this->policy()->id] + $this->prices($this->rx))->assertOk();

        return $this->rx->fresh();
    }

    /** @test */
    public function an_approved_share_splits_the_bill_and_the_patient_pays_only_their_part(): void
    {
        $rx = $this->pendingInsured();

        $this->as($this->clinic)->postJson("/care/prescriptions/{$rx->id}/insurer-decision", ['result' => 'approved', 'insurer_amount' => 9000, 'reference' => 'PA-77'])->assertOk();

        $rx->refresh();
        $this->assertEquals(9000, $rx->insurer_amount);
        $this->assertEquals(5000, $rx->patient_amount);
        $this->assertSame('PA-77', $rx->insurer_reference);
        $this->assertSame('unpaid', $rx->payment_status);
        Http::assertSent(fn ($r) => str_contains($r['message'] ?? '', 'insurer covers UGX 9,000') && str_contains($r['message'], 'you pay UGX 5,000'));

        $this->as($this->clinic)->postJson("/care/prescriptions/{$rx->id}/insurer-decision", ['result' => 'approved', 'insurer_amount' => 1])->assertStatus(409);
    }

    /** @test */
    public function the_insurer_cannot_be_recorded_as_paying_more_than_the_total(): void
    {
        $rx = $this->pendingInsured();

        $this->as($this->clinic)->postJson("/care/prescriptions/{$rx->id}/insurer-decision", ['result' => 'approved', 'insurer_amount' => 14001])->assertStatus(422);
        $this->as($this->clinic)->postJson("/care/prescriptions/{$rx->id}/insurer-decision", ['result' => 'approved'])->assertStatus(422);
        $this->as($this->other)->postJson("/care/prescriptions/{$rx->id}/insurer-decision", ['result' => 'approved', 'insurer_amount' => 100])->assertNotFound();
    }

    /** @test */
    public function full_cover_needs_no_payment_and_opens_delivery_at_once(): void
    {
        $rx = $this->pendingInsured();
        $this->as($this->clinic)->postJson("/care/prescriptions/{$rx->id}/insurer-decision", ['result' => 'approved', 'insurer_amount' => 14000])->assertOk();

        $rx->refresh();
        $this->assertSame('paid', $rx->payment_status);
        $this->assertSame('insurance', $rx->payment_method);
        $this->assertTrue($rx->canRequestDelivery());
    }

    /** @test */
    public function a_declined_claim_falls_back_to_the_full_price(): void
    {
        $rx = $this->pendingInsured();
        $this->as($this->clinic)->postJson("/care/prescriptions/{$rx->id}/insurer-decision", ['result' => 'declined', 'note' => 'Not on formulary'])->assertOk();

        $rx->refresh();
        $this->assertSame('self_pay', $rx->coverage_type);
        $this->assertEquals(14000, $rx->patient_amount);
        $this->assertSame('unpaid', $rx->payment_status);
        Http::assertSent(fn ($r) => str_contains($r['message'] ?? '', 'did not cover your medicine'));
    }

    // ---- patient pays ----

    private function unpaidShare(float $share = 5000): Prescription
    {
        $this->rx->update(['total_amount' => 14000, 'insurer_amount' => 14000 - $share, 'patient_amount' => $share, 'payment_status' => 'unpaid', 'coverage_type' => 'insurance', 'insurance_status' => 'approved']);

        return $this->rx->fresh();
    }

    /** @test */
    public function delivery_stays_closed_until_the_patients_share_is_paid(): void
    {
        $rx = $this->unpaidShare();
        $this->assertFalse($rx->canRequestDelivery());

        $this->post($this->link('patient.prescriptions.delivery', $rx), ['address' => 'Ntinda'])->assertRedirect();
        $this->assertNull($rx->fresh()->delivery_status);
    }

    /** @test */
    public function paying_the_share_by_mobile_money_then_the_webhook_opens_delivery(): void
    {
        $rx = $this->unpaidShare();

        $this->post($this->link('patient.prescriptions.pay.momo', $rx), ['phone' => '0701 555 666'])->assertRedirect();
        $rx->refresh();
        $this->assertSame('pending', $rx->payment_status);
        $this->assertSame('rx-tx-1', $rx->payment_reference);
        Http::assertSent(fn ($r) => ($r['amount'] ?? null) == 5000 && ($r['phone_number'] ?? null) === '+256701555666');

        $this->postJson('/marzpay/webhook', ['event_type' => 'collection.completed', 'transaction' => ['uuid' => 'rx-tx-1', 'reference' => 'x', 'status' => 'completed', 'amount' => 5000]])->assertOk();

        $rx->refresh();
        $this->assertSame('paid', $rx->payment_status);
        $this->assertTrue($rx->canRequestDelivery());
        Http::assertSent(fn ($r) => str_contains($r['message'] ?? '', 'Payment received for your medicine'));
    }

    /** @test */
    public function a_failed_payment_lets_the_patient_try_again(): void
    {
        $rx = $this->unpaidShare();
        $this->post($this->link('patient.prescriptions.pay.momo', $rx), ['phone' => '0701555666']);

        $this->postJson('/marzpay/webhook', ['event_type' => 'collection.failed', 'transaction' => ['uuid' => 'rx-tx-1', 'reference' => 'x', 'status' => 'failed', 'amount' => 5000]])->assertOk();

        $rx->refresh();
        $this->assertSame('unpaid', $rx->payment_status);
        $this->assertNull($rx->payment_reference);
    }

    /** @test */
    public function the_share_can_be_paid_from_the_wallet_only_when_it_covers_it(): void
    {
        $rx = $this->unpaidShare();
        Wallet::forPatient($this->patient)->credit(3000);

        $this->post($this->link('patient.prescriptions.pay.wallet', $rx))->assertRedirect();
        $this->assertSame('unpaid', $rx->fresh()->payment_status);
        $this->assertSame('3000.00', Wallet::forPatient($this->patient)->balance);

        Wallet::forPatient($this->patient)->credit(4000);
        $this->post($this->link('patient.prescriptions.pay.wallet', $rx))->assertRedirect();

        $this->assertSame('paid', $rx->fresh()->payment_status);
        $this->assertSame('2000.00', Wallet::forPatient($this->patient)->balance);

        $this->post($this->link('patient.prescriptions.pay.wallet', $rx))->assertRedirect();      // second try changes nothing
        $this->assertSame('2000.00', Wallet::forPatient($this->patient)->balance);
    }

    /** @test */
    public function another_patient_cannot_pay_or_see_the_prescription(): void
    {
        $rx = $this->unpaidShare();
        $stranger = $this->patient($this->other);

        $this->post($this->link('patient.prescriptions.pay.momo', $rx, $stranger), ['phone' => '0701555666'])->assertNotFound();
        $this->post($this->link('patient.prescriptions.pay.wallet', $rx, $stranger))->assertNotFound();
        $this->post('/my-visits/' . $this->patient->id . '/prescriptions/' . $rx->id . '/pay/wallet')->assertForbidden();   // unsigned
    }

    /** @test */
    public function the_patients_page_shows_the_split_and_the_payment_options(): void
    {
        $this->unpaidShare(5000);
        Wallet::forPatient($this->patient)->credit(9000);
        $url = URL::temporarySignedRoute('patient.visits', now()->addHour(), ['patient' => $this->patient->id]);

        $this->get($url)->assertOk()->assertSee('UGX 14,000')->assertSee('Insurer pays UGX 9,000')->assertSee('UGX 5,000')
            ->assertSee('with mobile money')->assertSee('Pay from wallet')->assertDontSee('Request delivery');
    }

    // ---- claims export ----

    /** @test */
    public function the_claims_csv_lists_only_my_paid_insurer_shares_and_submitted_ones_drop_out(): void
    {
        $mine = $this->unpaidShare();
        $mine->update(['payment_status' => 'paid', 'member_policy_id' => $this->policy()->id, 'insurer_reference' => 'PA-1']);

        $otherPatient = $this->patient($this->other);
        $theirs = $this->prescription($otherPatient);
        $theirs->update(['coverage_type' => 'insurance', 'insurance_status' => 'approved', 'insurer_amount' => 500, 'patient_amount' => 0, 'total_amount' => 500, 'payment_status' => 'paid', 'member_policy_id' => $this->policy('active', $otherPatient)->id]);

        $unpaid = $this->prescription();
        $unpaid->update(['coverage_type' => 'insurance', 'insurance_status' => 'approved', 'insurer_amount' => 800, 'payment_status' => 'unpaid']);

        $csv = $this->as($this->clinic)->get('/care/pharmacy/claims.csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('PA-1', $csv);
        $this->assertStringContainsString('Jubilee', $csv);
        $this->assertStringContainsString('Amoxicillin x3', $csv);
        $this->assertSame(2, substr_count($csv, "\n"));                                  // header + my one claim

        $this->as($this->clinic)->postJson('/care/pharmacy/claims/submitted', ['prescription_ids' => [$mine->id, $theirs->id, $unpaid->id]])->assertOk()->assertJson(['submitted' => 1]);

        $this->assertSame('submitted', $mine->fresh()->insurance_status);
        $this->assertSame('approved', $theirs->fresh()->insurance_status);
        $this->assertSame(1, substr_count($this->as($this->clinic)->get('/care/pharmacy/claims.csv')->streamedContent(), "\n"));
        $this->assertSame(2, substr_count($this->as($this->clinic)->get('/care/pharmacy/claims.csv?all=1')->streamedContent(), "\n"));
    }
}
