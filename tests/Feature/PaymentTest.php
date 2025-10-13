<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\Duration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private $appointment;
    private $student;
    private $doctor;
    private $school;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->school = School::create([
            'name' => 'Test School',
            'email' => 'school@test.com',
            'contact' => '123456789'
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'name' => 'Test Student',
            'grade' => 'Grade 10',
            'birth_date' => '2008-01-01',
            'parent_contact' => '256700000000'
        ]);

        $this->doctor = Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'doctor@test.com',
            'specialization' => 'General Practitioner',
            'contact' => '256700000001'
        ]);

        $duration = Duration::create([
            'minutes' => 30,
            'general_price' => 50000.00,
            'specialist_price' => 0,
            'type' => 'general',
            'is_active' => true
        ]);

        $this->appointment = Appointment::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'doctor_id' => $this->doctor->id,
            'duration_id' => $duration->id,
            'appointment_time' => now()->addDays(1),
            'reason' => 'Test appointment',
            'status' => 'awaiting_payment'
        ]);
    }

    public function test_payment_validation_rules_are_defined()
    {
        $rules = Payment::rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('reference_id', $rules);
        $this->assertStringContainsString('unique:payments,reference_id', $rules['reference_id']);
    }

    public function test_can_create_payment_with_unique_reference_id()
    {
        $paymentData = [
            'appointment_id' => $this->appointment->id,
            'amount' => 50000,
            'phone_number' => '256700000002',
            'reference_id' => 'UNIQUE_REF_001',
            'status' => 'pending'
        ];

        $payment = Payment::create($paymentData);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('UNIQUE_REF_001', $payment->reference_id);
        $this->assertDatabaseHas('payments', [
            'reference_id' => 'UNIQUE_REF_001',
            'appointment_id' => $this->appointment->id
        ]);
    }

    public function test_cannot_create_payment_with_duplicate_reference_id()
    {
        // Create first payment
        Payment::create([
            'appointment_id' => $this->appointment->id,
            'amount' => 50000,
            'phone_number' => '256700000002',
            'reference_id' => 'DUPLICATE_REF_001',
            'status' => 'pending'
        ]);

        // Attempt to create second payment with same reference_id
        $this->expectException(\Illuminate\Database\QueryException::class);

        Payment::create([
            'appointment_id' => $this->appointment->id,
            'amount' => 30000,
            'phone_number' => '256700000003',
            'reference_id' => 'DUPLICATE_REF_001', // Same reference_id
            'status' => 'pending'
        ]);
    }

    public function test_validation_fails_for_duplicate_reference_id()
    {
        // Create first payment
        Payment::create([
            'appointment_id' => $this->appointment->id,
            'amount' => 50000,
            'phone_number' => '256700000002',
            'reference_id' => 'VALIDATION_REF_001',
            'status' => 'pending'
        ]);

        $validator = Validator::make([
            'appointment_id' => $this->appointment->id,
            'amount' => 30000,
            'phone_number' => '256700000003',
            'reference_id' => 'VALIDATION_REF_001', // Duplicate
            'status' => 'pending'
        ], Payment::rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('reference_id', $validator->errors()->toArray());
        $this->assertStringContainsString('has already been taken', $validator->errors()->first('reference_id'));
    }

    public function test_validation_passes_for_unique_reference_id()
    {
        $validator = Validator::make([
            'appointment_id' => $this->appointment->id,
            'amount' => 50000,
            'phone_number' => '256700000002',
            'reference_id' => 'UNIQUE_VALIDATION_REF_001',
            'status' => 'pending'
        ], Payment::rules());

        $this->assertTrue($validator->passes());
    }

    public function test_payment_belongs_to_appointment()
    {
        $payment = Payment::create([
            'appointment_id' => $this->appointment->id,
            'amount' => 50000,
            'phone_number' => '256700000002',
            'reference_id' => 'RELATIONSHIP_REF_001',
            'status' => 'pending'
        ]);

        $this->assertInstanceOf(Appointment::class, $payment->appointment);
        $this->assertEquals($this->appointment->id, $payment->appointment->id);
    }

    public function test_payment_has_many_transactions()
    {
        $payment = Payment::create([
            'appointment_id' => $this->appointment->id,
            'amount' => 50000,
            'phone_number' => '256700000002',
            'reference_id' => 'TRANSACTIONS_REF_001',
            'status' => 'pending'
        ]);

        // Transactions relationship should exist (even if empty)
        $this->assertIsIterable($payment->transactions);
    }

    public function test_payment_amount_casting_works()
    {
        $payment = Payment::create([
            'appointment_id' => $this->appointment->id,
            'amount' => '50000.50',
            'phone_number' => '256700000002',
            'reference_id' => 'CASTING_REF_001',
            'status' => 'pending'
        ]);

        $this->assertIsString($payment->amount); // Laravel casts decimal to string
        $this->assertEquals('50000.50', $payment->amount);
    }

    public function test_payment_metadata_casting_works()
    {
        $metadata = ['transaction_id' => 'tx_123', 'provider' => 'momo'];

        $payment = Payment::create([
            'appointment_id' => $this->appointment->id,
            'amount' => 50000,
            'phone_number' => '256700000002',
            'reference_id' => 'METADATA_REF_001',
            'status' => 'pending',
            'metadata' => $metadata
        ]);

        $this->assertIsArray($payment->metadata);
        $this->assertEquals($metadata, $payment->metadata);
    }

    public function test_payment_fillable_attributes()
    {
        $payment = new Payment();

        $fillable = [
            'appointment_id',
            'amount',
            'phone_number',
            'reference_id',
            'status',
            'metadata'
        ];

        foreach ($fillable as $attribute) {
            $this->assertContains($attribute, $payment->getFillable());
        }
    }

    public function test_payment_status_enum_values()
    {
        $validStatuses = ['pending', 'completed', 'failed', 'cancelled'];

        foreach ($validStatuses as $status) {
            $payment = Payment::create([
                'appointment_id' => $this->appointment->id,
                'amount' => 50000,
                'phone_number' => '256700000002',
                'reference_id' => 'STATUS_REF_' . strtoupper($status),
                'status' => $status
            ]);

            $this->assertEquals($status, $payment->status);
        }
    }
}
