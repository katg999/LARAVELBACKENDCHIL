<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\Patient;
use App\Models\School;
use App\Services\MarzPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarzPayTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test environment variables
        config([
            'services.marzpay.base_url' => 'https://wallet.wearemarz.com/api/v1',
            'services.marzpay.api_key' => 'test_api_key',
            'services.marzpay.api_secret' => 'test_api_secret',
            'services.marzpay.auth_header' => 'Basic ' . base64_encode('test_api_key:test_api_secret'),
        ]);
    }

    public function test_can_collect_payment()
    {
        // Mock the HTTP client
        Http::fake([
            'https://wallet.wearemarz.com/api/v1/collect-money' => Http::response([
                'status' => 'success',
                'message' => 'Collection initiated successfully.',
                'data' => [
                    'transaction' => [
                        'uuid' => 'test-uuid-123',
                        'reference' => 'COL001',
                        'status' => 'processing'
                    ]
                ]
            ], 200)
        ]);

        $response = $this->postJson('/test/payments/collect', [
            'amount' => 1000,
            'phone_number' => '+256700000000',
            'description' => 'Test payment'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Payment collection initiated successfully'
                ]);
    }

    public function test_can_send_payment()
    {
        Http::fake([
            'https://wallet.wearemarz.com/api/v1/send-money' => Http::response([
                'status' => 'success',
                'message' => 'Send money initiated successfully.',
                'data' => [
                    'transaction' => [
                        'uuid' => 'send-uuid-123',
                        'reference' => 'SEND001',
                        'status' => 'processing'
                    ]
                ]
            ], 200)
        ]);

        $response = $this->postJson('/test/payments/send', [
            'amount' => 500,
            'phone_number' => '+256700000000',
            'description' => 'Test disbursement'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Payment sent successfully'
                ]);
    }

    public function test_validates_payment_request()
    {
        $response = $this->postJson('/test/payments/collect', [
            'amount' => 100, // Below minimum
            'phone_number' => 'invalid-phone',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_check_payment_status()
    {
        Http::fake([
            'https://wallet.wearemarz.com/api/v1/transactions/test-uuid' => Http::response([
                'status' => 'success',
                'data' => [
                    'transaction' => [
                        'uuid' => 'test-uuid',
                        'status' => 'completed',
                        'amount' => 1000
                    ]
                ]
            ], 200)
        ]);

        $response = $this->getJson('/test/payments/status/test-uuid');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);
    }

    public function test_can_get_account_balance()
    {
        Http::fake([
            'https://wallet.wearemarz.com/api/v1/balance' => Http::response([
                'status' => 'success',
                'data' => [
                    'balance' => [
                        'formatted' => '10,000.00',
                        'raw' => 10000,
                        'currency' => 'UGX'
                    ]
                ]
            ], 200)
        ]);

        $response = $this->getJson('/test/payments/balance');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);
    }

    public function test_handles_collection_webhook()
    {
        $school = School::factory()->create();
        $patient = Patient::factory()->create(['school_id' => $school->id]);
        
        // Create required related records
        $doctor = Doctor::create([
            'school_id' => $school->id,
            'name' => 'Test Doctor',
            'specialization' => 'General',
            'email' => 'doctor-webhook-completed@test.com',
            'contact' => '+256700000010'
        ]);
        
        $duration = Duration::create([
            'minutes' => 30,
            'duration_type' => 'general',
            'price' => 1000,
            'is_active' => true
        ]);
        
        $appointment = Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Test appointment',
            'status' => 'awaiting_payment',
            'payment_reference' => 'test-uuid'
        ]);

        $webhookPayload = [
            'event_type' => 'collection.completed',
            'transaction' => [
                'uuid' => 'test-uuid',
                'reference' => 'appointment-' . $appointment->id . '-123',
                'status' => 'completed',
                'amount' => 1000
            ]
        ];

        $response = $this->postJson('/marzpay/webhook', $webhookPayload);

        $response->assertStatus(200)
                ->assertSee('Webhook processed successfully');

        // Refresh appointment and check status
        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);
        $this->assertEquals('completed', $appointment->payment_status);
    }

    public function test_handles_failed_collection_webhook()
    {
        $school = School::factory()->create();
        $patient = Patient::factory()->create(['school_id' => $school->id]);
        
        // Create required related records
        $doctor = Doctor::create([
            'school_id' => $school->id,
            'name' => 'Test Doctor',
            'specialization' => 'General',
            'email' => 'doctor-webhook-failed@test.com',
            'contact' => '+256700000011'
        ]);
        
        $duration = Duration::create([
            'minutes' => 30,
            'duration_type' => 'general',
            'price' => 1000,
            'is_active' => true
        ]);
        
        $appointment = Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Test appointment',
            'status' => 'awaiting_payment',
            'payment_reference' => 'test-uuid'
        ]);

        $webhookPayload = [
            'event_type' => 'collection.failed',
            'transaction' => [
                'uuid' => 'test-uuid',
                'reference' => 'appointment-' . $appointment->id . '-123',
                'status' => 'failed',
                'amount' => 1000
            ]
        ];

        $response = $this->postJson('/marzpay/webhook', $webhookPayload);

        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('failed', $appointment->payment_status);
    }

    public function test_handles_failed_collection_updates_existing_transaction()
    {
        $school = School::factory()->create();
        $patient = Patient::factory()->create(['school_id' => $school->id]);

        // Create required related records
        $doctor = Doctor::create([
            'school_id' => $school->id,
            'name' => 'Test Doctor',
            'specialization' => 'General',
            'email' => 'doctor-webhook-update-existing@test.com',
            'contact' => '+256700000012'
        ]);

        $duration = Duration::create([
            'minutes' => 30,
            'duration_type' => 'general',
            'price' => 1000,
            'is_active' => true
        ]);

        $appointment = Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Test appointment',
            'status' => 'awaiting_payment',
            'payment_reference' => 'test-uuid'
        ]);

        // Create an existing transaction with pending status
        $payment = \App\Models\Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => 1000,
            'phone_number' => '+256700000000',
            'reference_id' => 'test-uuid',
            'status' => 'pending',
        ]);

        $existingTransaction = \App\Models\Transaction::create([
            'payment_id' => $payment->id,
            'reference_id' => 'test-uuid',
            'amount' => 1000,
            'status' => 'pending',
            'transaction_id' => 'test-uuid',
            'provider' => 'marzpay',
            'provider_reference' => 'test-uuid',
            'marzpay_uuid' => 'test-uuid',
            'country' => 'UG',
            'description' => 'Appointment payment pending',
            'transaction_type' => 'collection',
            'webhook_event_type' => 'collection.pending',
        ]);

        $webhookPayload = [
            'event_type' => 'collection.failed',
            'transaction' => [
                'uuid' => 'test-uuid',
                'reference' => 'appointment-' . $appointment->id . '-123',
                'status' => 'failed',
                'amount' => 1000
            ]
        ];

        $response = $this->postJson('/marzpay/webhook', $webhookPayload);

        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('failed', $appointment->payment_status);

        // Verify the existing transaction was updated, not a new one created
        $updatedTransaction = \App\Models\Transaction::where('reference_id', 'test-uuid')->first();
        $this->assertEquals(1, \App\Models\Transaction::where('reference_id', 'test-uuid')->count());
        $this->assertEquals('failed', $updatedTransaction->status);
        $this->assertEquals('collection.failed', $updatedTransaction->webhook_event_type);
        $this->assertNotNull($updatedTransaction->processed_at);
    }

    public function test_handles_failed_collection_creates_new_when_no_existing()
    {
        $school = School::factory()->create();
        $patient = Patient::factory()->create(['school_id' => $school->id]);

        // Create required related records
        $doctor = Doctor::create([
            'school_id' => $school->id,
            'name' => 'Test Doctor',
            'specialization' => 'General',
            'email' => 'doctor-webhook-new@test.com',
            'contact' => '+256700000013'
        ]);

        $duration = Duration::create([
            'minutes' => 30,
            'duration_type' => 'general',
            'price' => 1000,
            'is_active' => true
        ]);

        $appointment = Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Test appointment',
            'status' => 'awaiting_payment',
            'payment_reference' => 'test-uuid-new'
        ]);

        // Debug: Check if appointment was created
        $this->assertNotNull($appointment);
        $this->assertEquals('test-uuid-new', $appointment->payment_reference);

        $webhookPayload = [
            'event_type' => 'collection.failed',
            'transaction' => [
                'uuid' => 'test-uuid-new',
                'reference' => 'appointment-' . $appointment->id . '-123',
                'status' => 'failed',
                'amount' => 1000
            ]
        ];

        $response = $this->postJson('/marzpay/webhook', $webhookPayload);

        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('failed', $appointment->payment_status);

        // A transaction should be created for webhook tracking purposes
        $transaction = \App\Models\Transaction::where('reference_id', 'test-uuid-new')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('failed', $transaction->status);
        $this->assertEquals('collection.failed', $transaction->webhook_event_type);
        $this->assertNull($transaction->payment_id); // No associated payment record
    }

    public function test_handles_failed_collection_does_not_update_successful_transaction()
    {
        $school = School::factory()->create();
        $patient = Patient::factory()->create(['school_id' => $school->id]);

        // Create required related records
        $doctor = Doctor::create([
            'school_id' => $school->id,
            'name' => 'Test Doctor',
            'specialization' => 'General',
            'email' => 'doctor-webhook-successful@test.com',
            'contact' => '+256700000014'
        ]);

        $duration = Duration::create([
            'minutes' => 30,
            'duration_type' => 'general',
            'price' => 1000,
            'is_active' => true
        ]);

        $appointment = Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Test appointment',
            'status' => 'awaiting_payment',
            'payment_reference' => 'test-uuid-successful'
        ]);

        // Create an existing transaction with successful status
        $payment = \App\Models\Payment::create([
            'appointment_id' => $appointment->id,
            'amount' => 1000,
            'phone_number' => '+256700000000',
            'reference_id' => 'test-uuid-successful',
            'status' => 'completed',
        ]);

        $existingTransaction = \App\Models\Transaction::create([
            'payment_id' => $payment->id,
            'reference_id' => 'test-uuid-successful',
            'amount' => 1000,
            'status' => 'successful',
            'transaction_id' => 'test-uuid-successful',
            'provider' => 'marzpay',
            'provider_reference' => 'test-uuid-successful',
            'marzpay_uuid' => 'test-uuid-successful',
            'country' => 'UG',
            'description' => 'Appointment payment successful',
            'transaction_type' => 'collection',
            'webhook_event_type' => 'collection.completed',
            'processed_at' => now(),
        ]);

        $webhookPayload = [
            'event_type' => 'collection.failed',
            'transaction' => [
                'uuid' => 'test-uuid-successful',
                'reference' => 'appointment-' . $appointment->id . '-123',
                'status' => 'failed',
                'amount' => 1000
            ]
        ];

        $response = $this->postJson('/marzpay/webhook', $webhookPayload);

        $response->assertStatus(200);

        // Verify the existing successful transaction was not updated
        $transaction = \App\Models\Transaction::where('reference_id', 'test-uuid-successful')->first();
        $this->assertEquals('successful', $transaction->status);
        $this->assertEquals('collection.completed', $transaction->webhook_event_type);

        // A new failed transaction should be created
        $failedTransactions = \App\Models\Transaction::where('reference_id', 'test-uuid-successful')
            ->where('status', 'failed')
            ->get();
        $this->assertCount(1, $failedTransactions);
    }

    public function test_handles_invalid_webhook_payload()
    {
        $response = $this->postJson('/marzpay/webhook', [
            'invalid' => 'payload'
        ]);

        $response->assertStatus(400)
                ->assertSee('Invalid webhook payload');
    }

    public function test_appointment_checkout_creates_payment_request()
    {
        Http::fake([
            'https://wallet.wearemarz.com/api/v1/collect-money' => Http::response([
                'status' => 'success',
                'message' => 'Collection initiated successfully.',
                'data' => [
                    'transaction' => [
                        'uuid' => 'checkout-uuid-123',
                        'reference' => 'COL001',
                        'status' => 'processing'
                    ]
                ]
            ], 200)
        ]);

        $school = School::factory()->create();
        $patient = Patient::factory()->create([
            'school_id' => $school->id,
            'contact_number' => '+256700000000'
        ]);
        
        // Create required related records
        $doctor = Doctor::create([
            'school_id' => $school->id,
            'name' => 'Test Doctor',
            'specialization' => 'General',
            'email' => 'doctor-checkout@test.com',
            'contact' => '+256700000015'
        ]);
        
        $duration = Duration::create([
            'minutes' => 30,
            'duration_type' => 'general',
            'price' => 1000,
            'is_active' => true
        ]);
        
        $appointment = Appointment::create([
            'school_id' => $school->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Test appointment',
            'status' => 'awaiting_payment'
        ]);

        $response = $this->postJson('/appointment/checkout', [
            'appointment_id' => $appointment->id,
            'amount' => 1000,
            'phone_number' => '+256700000000'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Payment request sent. Please approve on your phone.'
                ]);

        $appointment->refresh();
        $this->assertNotNull($appointment->payment_reference);
    }

    public function test_marzpay_service_balance_method()
    {
        $service = app(MarzPayService::class);

        Http::fake([
            'https://wallet.wearemarz.com/api/v1/balance' => Http::response([
                'status' => 'success',
                'data' => [
                    'balance' => [
                        'formatted' => '5,000.00',
                        'raw' => 5000,
                        'currency' => 'UGX'
                    ]
                ]
            ], 200)
        ]);

        $result = $service->getBalance();

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(5000, $result['data']['balance']['raw']);
    }

    public function test_marzpay_service_collect_money_method()
    {
        $service = app(MarzPayService::class);

        Http::fake([
            'https://wallet.wearemarz.com/api/v1/collect-money' => Http::response([
                'status' => 'success',
                'message' => 'Collection initiated successfully.',
                'data' => [
                    'transaction' => [
                        'uuid' => 'collect-uuid-123',
                        'status' => 'processing'
                    ]
                ]
            ], 200)
        ]);

        $data = [
            'amount' => 1000,
            'phone_number' => '+256700000000',
            'country' => 'UG',
            'reference' => 'test-ref-123',
            'description' => 'Test collection'
        ];

        $result = $service->collectMoney($data);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('collect-uuid-123', $result['data']['transaction']['uuid']);
    }

    public function test_marzpay_service_send_money_method()
    {
        $service = app(MarzPayService::class);

        Http::fake([
            'https://wallet.wearemarz.com/api/v1/send-money' => Http::response([
                'status' => 'success',
                'message' => 'Send money initiated successfully.',
                'data' => [
                    'transaction' => [
                        'uuid' => 'send-uuid-123',
                        'status' => 'processing'
                    ]
                ]
            ], 200)
        ]);

        $data = [
            'amount' => 500,
            'phone_number' => '+256700000000',
            'country' => 'UG',
            'reference' => 'test-send-ref-123',
            'description' => 'Test send'
        ];

        $result = $service->sendMoney($data);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('send-uuid-123', $result['data']['transaction']['uuid']);
    }

    public function test_marzpay_service_handles_api_errors()
    {
        $service = app(MarzPayService::class);

        Http::fake([
            'https://wallet.wearemarz.com/api/v1/balance' => Http::response([
                'status' => 'error',
                'message' => 'Invalid API credentials'
            ], 401)
        ]);

        $this->expectException(\Exception::class);
        $service->getBalance();
    }

    public function test_phone_number_validation_for_payments()
    {
        // Test invalid phone number format
        $response = $this->postJson('/test/payments/collect', [
            'amount' => 1000,
            'phone_number' => '256700000000', // Missing +
        ]);

        $response->assertStatus(422);

        // Test valid phone number format
        Http::fake([
            'https://wallet.wearemarz.com/api/v1/collect-money' => Http::response([
                'status' => 'success',
                'message' => 'Collection initiated successfully.',
                'data' => ['transaction' => ['uuid' => 'test-uuid']]
            ], 200)
        ]);

        $response = $this->postJson('/test/payments/collect', [
            'amount' => 1000,
            'phone_number' => '+256700000000', // Valid format
        ]);

        $response->assertStatus(200);
    }

    public function test_amount_validation_limits()
    {
        // Test amount below minimum
        $response = $this->postJson('/test/payments/collect', [
            'amount' => 100, // Below 500 minimum
            'phone_number' => '+256700000000',
        ]);

        $response->assertStatus(422);

        // Test amount above maximum
        $response = $this->postJson('/test/payments/collect', [
            'amount' => 20000000, // Above 10,000,000 maximum
            'phone_number' => '+256700000000',
        ]);

        $response->assertStatus(422);
    }
}