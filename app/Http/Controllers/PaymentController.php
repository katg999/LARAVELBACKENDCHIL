<?php
// app/Http/Controllers/PaymentController.php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\MarzPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $marzPayService;

    public function __construct(MarzPayService $marzPayService)
    {
        $this->marzPayService = $marzPayService;
    }

    public function index()
    {
        return view('payment');
    }

    // Show pay page for an appointment
    public function showAppointmentPayForm(Appointment $appointment)
    {
        if ($appointment->status !== 'awaiting_payment') {
            // Redirect to appropriate booking page with success message
            if ($appointment->school_id) {
                return redirect()->route('book-doctor', ['school' => $appointment->school_id])
                    ->with('success', 'This appointment has already been confirmed and paid for.');
            } elseif ($appointment->health_facility_id) {
                return redirect()->route('health-facility.book-doctor', ['id' => $appointment->health_facility_id])
                    ->with('success', 'This appointment has already been confirmed and paid for.');
            } else {
                return redirect('/')->with('success', 'This appointment has already been confirmed and paid for.');
            }
        }
        // Load relations and pass sidebar context so menu renders
        $appointment->load(['school', 'doctor', 'patient', 'healthFacility', 'duration']);
        $school = $appointment->school;
        $doctor = $appointment->doctor;
        $healthFacility = $appointment->healthFacility;
        return view('payments/appointment-pay', compact('appointment', 'school', 'doctor', 'healthFacility'));
    }

    // Initialize API User (one-time setup) - Not needed for MarzPay
    public function initApiUser()
    {
        return response()->json([
            'success' => true,
            'message' => 'MarzPay does not require API user initialization'
        ]);
    }

    // Get API User details - Not applicable for MarzPay
    public function getApiUser()
    {
        return response()->json([
            'success' => true,
            'message' => 'MarzPay uses API key authentication'
        ]);
    }

    // Request payment using MarzPay
    public function requestPayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:500|max:10000000',
            'phone_number' => 'required|string|regex:/^\+256\d{9}$/',
            'external_id' => 'nullable|string'
        ]);

        try {
            $data = [
                'amount' => $validated['amount'],
                'phone_number' => $validated['phone_number'],
                'country' => 'UG',
                'reference' => (string) Str::uuid(),
                'description' => 'Payment request',
                'callback_url' => route('marzpay.webhook'),
            ];

            $result = $this->marzPayService->collectMoney($data);

            if (($result['status'] ?? null) === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment collection initiated successfully',
                    'data' => $result['data'],
                    'reference_id' => $result['data']['transaction']['uuid'] ?? null
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to initiate payment'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment Request Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing payment'
            ], 500);
        }
    }

    /**
     * Send payment to customer (disbursement)
     */
    public function sendPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:500|max:10000000',
            'phone_number' => 'required|string|regex:/^\+256\d{9}$/',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $data = [
                'amount' => $request->amount,
                'phone_number' => $request->phone_number,
                'country' => 'UG',
                'reference' => (string) Str::uuid(),
                'description' => $request->description ?? 'Payment disbursement',
                'callback_url' => route('marzpay.webhook'),
            ];

            $response = $this->marzPayService->sendMoney($data);

            if (($response['status'] ?? null) === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment sent successfully',
                    'data' => $response['data']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'Payment sending failed'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment Sending Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending payment'
            ], 500);
        }
    }

    // Check payment status
    public function paymentStatus($referenceId)
    {
        try {
            $status = $this->marzPayService->getTransaction($referenceId);
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Payment Status Check Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to check payment status'
            ], 500);
        }
    }

    // Get account balance
    public function accountBalance()
    {
        try {
            $balance = $this->marzPayService->getBalance();
            return response()->json([
                'success' => true,
                'data' => $balance
            ]);
        } catch (\Exception $e) {
            Log::error('Account Balance Check Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to check account balance'
            ], 500);
        }
    }

    // Handle MarzPay callback/webhook
    public function handleCallback(Request $request)
    {
        try {
            $payload = $request->all();

            // Basic security checks
            if (!$this->validateWebhookRequest($request)) {
                Log::warning('Invalid webhook request received', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'headers' => $request->headers->all()
                ]);
                return response('Unauthorized', 401);
            }

            // TODO: Implement signature verification when MarzPay provides signature details
            // $signature = $request->header('X-MarzPay-Signature');
            // if (!$this->verifyWebhookSignature($payload, $signature)) {
            //     return response('Invalid signature', 401);
            // }

            Log::info('MarzPay Webhook Received:', $payload);

            // Process webhook based on event type
            $eventType = $payload['event_type'] ?? null;
            $transaction = $payload['transaction'] ?? null;

            if (!$transaction) {
                Log::warning('Invalid MarzPay webhook payload - missing transaction data');
                return response('Invalid webhook payload', 400);
            }

            switch ($eventType) {
                case 'collection.completed':
                    $this->handleSuccessfulCollection($transaction);
                    break;

                case 'collection.failed':
                    $this->handleFailedCollection($transaction);
                    break;

                case 'collection.pending':
                    $this->handlePendingCollection($transaction);
                    break;

                case 'collection.cancelled':
                    $this->handleCancelledCollection($transaction);
                    break;

                default:
                    Log::info('Unhandled MarzPay webhook event type: ' . $eventType);
            }

            return response('Webhook processed successfully', 200);

        } catch (\Exception $e) {
            Log::error('MarzPay Webhook Processing Error: ' . $e->getMessage());
            return response('Webhook processing failed', 500);
        }
    }

    // Appointment checkout from web: triggers a MarzPay payment request for an appointment
    public function createAppointmentCheckout(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'phone_number' => 'nullable|string',
            'amount' => 'nullable|numeric',
        ]);

        $appointment = Appointment::with(['patient', 'healthFacility'])->findOrFail($validated['appointment_id']);

        // Determine payer phone and amount
        $phone = $validated['phone_number'] ?? ($appointment->patient->contact_number ?? $appointment->patient->parent_contact ?? $appointment->healthFacility->contact ?? null);
        if (!$phone) {
            $message = 'No phone number available for this appointment.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            // Redirect to pay page instead of back() to avoid loops
            return redirect()->route('payment.appointment.pay', $appointment->id)->with('error', $message);
        }

        // Normalize phone to international format (+256xxxxxxxxx)
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '07')) {
            // 07XXXXXXXX -> +2567XXXXXXXX
            $phone = '+256' . substr($digits, 1);
        } elseif (str_starts_with($digits, '2560')) {
            // 2560XXXXXXXX -> +2567XXXXXXXX (drop the 0)
            $phone = '+256' . substr($digits, 3);
        } elseif (str_starts_with($digits, '0') && strlen($digits) >= 9) {
            // 0XXXXXXXXX -> +256XXXXXXXXX (general fallback)
            $phone = '+256' . substr($digits, 1);
        } elseif (str_starts_with($digits, '256')) {
            $phone = '+' . $digits;
        } else {
            // Last resort: assume already in international without country code is 9 digits starting with 7
            if (strlen($digits) === 9 && $digits[0] === '7') {
                $phone = '+256' . $digits;
            } else {
                $phone = '+' . $digits; // pass as-is with plus
            }
        }

        $amount = $validated['amount'] ?? ($appointment->duration ? $appointment->duration->getPriceForDoctor($appointment->doctor) : 1.00); // allow override on pay page

        try {
            $data = [
                'amount' => $amount,
                'phone_number' => $phone,
                'country' => 'UG',
                'reference' => (string) Str::uuid(),
                'description' => 'Appointment payment - ' . $appointment->id,
                'callback_url' => route('marzpay.webhook'),
            ];

            $result = $this->marzPayService->collectMoney($data);

            if (($result['status'] ?? null) === 'success') {
                // Create Payment record
                $payment = \App\Models\Payment::create([
                    'appointment_id' => $appointment->id,
                    'amount' => $amount,
                    'phone_number' => $phone,
                    'reference_id' => $result['data']['transaction']['uuid'] ?? (string) Str::uuid(),
                    'status' => 'pending',
                    'metadata' => [
                        'marzpay_response' => $result,
                        'requested_at' => now(),
                    ]
                ]);

                // Store payment reference on appointment for tracking
                $appointment->payment_reference = $payment->reference_id;
                $appointment->payment_status = 'pending';
                $appointment->save();

                // Create initial Transaction record
                \App\Models\Transaction::create([
                    'payment_id' => $payment->id,
                    'reference_id' => $payment->reference_id,
                    'amount' => $amount,
                    'status' => 'pending',
                    'transaction_id' => $result['data']['transaction']['uuid'] ?? null,
                    'provider' => 'marzpay',
                    'provider_reference' => $result['data']['transaction']['uuid'] ?? null,
                    'marzpay_uuid' => $result['data']['transaction']['uuid'] ?? null,
                    'country' => 'UG',
                    'description' => 'Appointment payment - ' . $appointment->id,
                    'transaction_type' => 'collection',
                    'webhook_event_type' => 'collection.pending',
                    'collection_data' => $result,
                ]);

                $message = 'Payment request sent. Please approve on your phone.';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'status' => 'pending',
                        'reference_id' => $appointment->payment_reference
                    ]);
                }
                return redirect()->route('payment.appointment.pay', $appointment->id)->with('success', $message);
            }

            $message = $result['message'] ?? 'Failed to initiate payment';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return redirect()->route('payment.appointment.pay', $appointment->id)->with('error', $message);

        } catch (\Exception $e) {
            Log::error('Appointment Checkout Error: ' . $e->getMessage());
            $message = 'An error occurred while processing payment';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message]);
            }
            return redirect()->route('payment.appointment.pay', $appointment->id)->with('error', $message);
        }
    }

    // Check payment status for an appointment
    public function checkAppointmentPaymentStatus(Appointment $appointment)
    {
        $payment = \App\Models\Payment::where('appointment_id', $appointment->id)->latest()->first();

        $status = [
            'appointment_status' => $appointment->status,
            'payment_status' => $appointment->payment_status,
            'payment_reference' => $appointment->payment_reference,
        ];

        if ($payment) {
            $status['payment_record'] = [
                'status' => $payment->status,
                'reference_id' => $payment->reference_id,
                'amount' => $payment->amount,
                'created_at' => $payment->created_at,
            ];
        }

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }

    // Mark appointment as paid (manual success landing) - Only allow if payment is confirmed
    public function appointmentSuccess(Appointment $appointment)
    {
        // Only allow confirmation if payment is actually completed
        if ($appointment->status !== 'confirmed' || $appointment->payment_status !== 'completed') {
            return redirect()->route('payment.appointment.pay', $appointment->id)
                ->with('error', 'Payment has not been confirmed yet. Please check your payment status.');
        }

        return redirect()->back()->with('success', 'Appointment is confirmed and payment completed.');
    }



    // Send appointment confirmation email to doctor
    protected function sendAppointmentConfirmationEmail(Appointment $appointment)
    {
        $doctor = $appointment->doctor;
        $patient = $appointment->patient;
        $institution = $appointment->school ?? $appointment->healthFacility;

        if ($doctor && $doctor->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($doctor->email)->send(
                    new \App\Mail\AppointmentConfirmationMail($appointment, $doctor, $patient, $institution)
                );
            } catch (\Exception $e) {
                \Log::error('Failed to send appointment confirmation email: ' . $e->getMessage());
            }
        }
    }

    // Cancel payment flow for appointment
    public function appointmentCancel(Appointment $appointment)
    {
        // Optionally set a specific status; keep awaiting_payment so user can retry
        if ($appointment->healthFacility) {
            return redirect()->route('health-facility.dashboard', ['id' => $appointment->healthFacility->id])->with('error', 'Payment was canceled. You can try again.');
        }

        return redirect('/')->with('error', 'Payment was canceled. You can try again.');
    }

    /**
     * Confirm dummy payment for testing purposes
     * This method simulates payment confirmation for testing the workflow
     */
    public function confirmDummyPayment(Appointment $appointment)
    {
        // Check if appointment is awaiting payment
        if ($appointment->status !== 'awaiting_payment') {
            return response()->json([
                'success' => false,
                'message' => 'Appointment is not awaiting payment confirmation.'
            ], 400);
        }

        // Change status to confirmed
        $appointment->status = 'confirmed';
        $appointment->save();

        // Send confirmation email to doctor
        $this->sendAppointmentConfirmationEmail($appointment);

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed successfully. Doctor has been notified.'
        ]);
    }

    /**
     * Handle successful collection webhook
     */
    private function handleSuccessfulCollection($transaction)
    {
        try {
            $reference = $transaction['reference'] ?? null;
            $uuid = $transaction['uuid'] ?? null;

            // Find appointment by payment_reference (could be uuid or reference)
            $appointment = null;
            if ($uuid) {
                $appointment = Appointment::where('payment_reference', $uuid)->first();
            }
            if (!$appointment && $reference) {
                $appointment = Appointment::where('payment_reference', $reference)->first();
            }

            // Also check Payment table for matching reference_id
            $payment = null;
            if ($uuid) {
                $payment = \App\Models\Payment::where('reference_id', $uuid)->first();
            }
            if (!$payment && $reference) {
                $payment = \App\Models\Payment::where('reference_id', $reference)->first();
            }

            // If we found a payment but no appointment, get appointment from payment
            if (!$appointment && $payment) {
                $appointment = $payment->appointment;
            }

            if ($appointment) {
                $appointment->status = 'confirmed';
                $appointment->payment_status = 'completed';
                $appointment->save();

                // Update Payment record
                if ($payment) {
                    $payment->update(['status' => 'completed']);
                }

                // Send confirmation email to doctor
                if ($appointment->doctor && $appointment->doctor->email) {
                    try {
                        Mail::to($appointment->doctor->email)->send(new \App\Mail\DoctorAppointmentConfirmationMail($appointment));
                        Log::info('Doctor confirmation email sent', [
                            'appointment_id' => $appointment->id,
                            'doctor_email' => $appointment->doctor->email
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send doctor confirmation email: ' . $e->getMessage(), [
                            'appointment_id' => $appointment->id,
                            'doctor_email' => $appointment->doctor->email
                        ]);
                    }
                }

                // Update existing transaction or create new one for successful collection
                $existingTransaction = \App\Models\Transaction::where('reference_id', $uuid ?? $reference)
                    ->whereNotIn('status', ['successful', 'failed']) // Don't update already completed transactions
                    ->first();

                if ($existingTransaction) {
                    // Update existing pending transaction
                    $existingTransaction->update([
                        'status' => 'successful',
                        'webhook_event_type' => 'collection.completed',
                        'collection_data' => $transaction,
                        'processed_at' => now(),
                    ]);
                } else {
                    // Create new transaction if no pending one exists
                    \App\Models\Transaction::create([
                        'payment_id' => $payment?->id,
                        'reference_id' => $uuid ?? $reference,
                        'amount' => $transaction['amount'] ?? 0,
                        'status' => 'successful',
                        'transaction_id' => $uuid,
                        'provider' => 'marzpay',
                        'provider_reference' => $uuid,
                        'marzpay_uuid' => $uuid,
                        'country' => 'UG',
                        'description' => 'Appointment payment completed - ' . $appointment->id,
                        'transaction_type' => 'collection',
                        'webhook_event_type' => 'collection.completed',
                        'collection_data' => $transaction,
                        'processed_at' => now(),
                    ]);
                }

                Log::info('Appointment payment completed', [
                    'appointment_id' => $appointment->id,
                    'transaction_uuid' => $uuid
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Handle Successful Collection Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle failed collection webhook
     */
    private function handleFailedCollection($transaction)
    {
        try {
            $reference = $transaction['reference'] ?? null;
            $uuid = $transaction['uuid'] ?? null;

            // Find appointment by payment_reference (could be uuid or reference)
            $appointment = null;
            if ($uuid) {
                $appointment = Appointment::where('payment_reference', $uuid)->first();
            }
            if (!$appointment && $reference) {
                $appointment = Appointment::where('payment_reference', $reference)->first();
            }

            // Also check Payment table for matching reference_id
            $payment = null;
            if ($uuid) {
                $payment = \App\Models\Payment::where('reference_id', $uuid)->first();
            }
            if (!$payment && $reference) {
                $payment = \App\Models\Payment::where('reference_id', $reference)->first();
            }

            // If we found a payment but no appointment, get appointment from payment
            if (!$appointment && $payment) {
                $appointment = $payment->appointment;
            }

            if ($appointment) {
                $appointment->payment_status = 'failed';
                $appointment->save();

                // Don't update Payment record for failed collections - only successful payments are registered
            }

            // Update existing transaction or create new one for failed collection
            $existingTransaction = \App\Models\Transaction::where('reference_id', $uuid ?? $reference)
                ->whereNotIn('status', ['successful', 'failed']) // Don't update already completed transactions
                ->first();

            if ($existingTransaction) {
                // Update existing transaction to failed
                $existingTransaction->update([
                    'status' => 'failed',
                    'webhook_event_type' => 'collection.failed',
                    'collection_data' => $transaction,
                    'processed_at' => now(),
                ]);
            } else {
                // Create new transaction if no existing one found or all existing are already final
                \App\Models\Transaction::create([
                    'payment_id' => $payment?->id,
                    'reference_id' => $uuid ?? $reference,
                    'amount' => $transaction['amount'] ?? 0,
                    'status' => 'failed',
                    'transaction_id' => $uuid,
                    'provider' => 'marzpay',
                    'provider_reference' => $uuid,
                    'marzpay_uuid' => $uuid,
                    'country' => 'UG',
                    'description' => 'Appointment payment failed - ' . ($appointment ? $appointment->id : 'unknown'),
                    'transaction_type' => 'collection',
                    'webhook_event_type' => 'collection.failed',
                    'collection_data' => $transaction,
                    'processed_at' => now(),
                ]);
            }

            Log::warning('Appointment payment failed', [
                'appointment_id' => $appointment?->id,
                'transaction_uuid' => $uuid
            ]);

        } catch (\Exception $e) {
            Log::error('Handle Failed Collection Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle pending collection webhook
     */
    private function handlePendingCollection($transaction)
    {
        try {
            $reference = $transaction['reference'] ?? null;
            $uuid = $transaction['uuid'] ?? null;

            // Find appointment by payment_reference (could be uuid or reference)
            $appointment = null;
            if ($uuid) {
                $appointment = Appointment::where('payment_reference', $uuid)->first();
            }
            if (!$appointment && $reference) {
                $appointment = Appointment::where('payment_reference', $reference)->first();
            }

            // Also check Payment table for matching reference_id
            $payment = null;
            if ($uuid) {
                $payment = \App\Models\Payment::where('reference_id', $uuid)->first();
            }
            if (!$payment && $reference) {
                $payment = \App\Models\Payment::where('reference_id', $reference)->first();
            }

            // If we found a payment but no appointment, get appointment from payment
            if (!$appointment && $payment) {
                $appointment = $payment->appointment;
            }

            if ($appointment) {
                $appointment->payment_status = 'pending';
                $appointment->save();

                // Don't update Payment record for pending collections - only successful payments are registered
            }

            // Create or update transaction record for pending collection
            $existingTransaction = \App\Models\Transaction::where('reference_id', $uuid ?? $reference)
                ->whereNotIn('status', ['successful', 'failed']) // Don't update already completed transactions
                ->first();

            if ($existingTransaction) {
                // Update existing transaction
                $existingTransaction->update([
                    'webhook_event_type' => 'collection.pending',
                    'collection_data' => $transaction,
                ]);
            } else {
                // Create new transaction if none exists or all existing are already final
                \App\Models\Transaction::create([
                    'payment_id' => $payment?->id,
                    'reference_id' => $uuid ?? $reference,
                    'amount' => $transaction['amount'] ?? 0,
                    'status' => 'pending',
                    'transaction_id' => $uuid,
                    'provider' => 'marzpay',
                    'provider_reference' => $uuid,
                    'marzpay_uuid' => $uuid,
                    'country' => 'UG',
                    'description' => 'Appointment payment pending - ' . ($appointment ? $appointment->id : 'unknown'),
                    'transaction_type' => 'collection',
                    'webhook_event_type' => 'collection.pending',
                    'collection_data' => $transaction,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Handle Pending Collection Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle cancelled collection webhook
     */
    private function handleCancelledCollection($transaction)
    {
        try {
            $reference = $transaction['reference'] ?? null;
            $uuid = $transaction['uuid'] ?? null;

            // Find appointment by payment_reference (could be uuid or reference)
            $appointment = null;
            if ($uuid) {
                $appointment = Appointment::where('payment_reference', $uuid)->first();
            }
            if (!$appointment && $reference) {
                $appointment = Appointment::where('payment_reference', $reference)->first();
            }

            // Also check Payment table for matching reference_id
            $payment = null;
            if ($uuid) {
                $payment = \App\Models\Payment::where('reference_id', $uuid)->first();
            }
            if (!$payment && $reference) {
                $payment = \App\Models\Payment::where('reference_id', $reference)->first();
            }

            // If we found a payment but no appointment, get appointment from payment
            if (!$appointment && $payment) {
                $appointment = $payment->appointment;
            }

            if ($appointment) {
                $appointment->payment_status = 'cancelled';
                $appointment->save();

                // Don't update Payment record for cancelled collections - only successful payments are registered
            }

            // Create or update transaction record for cancelled collection
            $existingTransaction = \App\Models\Transaction::where('reference_id', $uuid ?? $reference)
                ->whereNotIn('status', ['successful', 'failed']) // Don't update already completed transactions
                ->first();

            if ($existingTransaction) {
                // Update existing pending transaction
                $existingTransaction->update([
                    'status' => 'cancelled',
                    'webhook_event_type' => 'collection.cancelled',
                    'collection_data' => $transaction,
                    'processed_at' => now(),
                ]);
            } else {
                // Create new transaction if no pending one exists
                \App\Models\Transaction::create([
                    'payment_id' => $payment?->id,
                    'reference_id' => $uuid ?? $reference,
                    'amount' => $transaction['amount'] ?? 0,
                    'status' => 'cancelled',
                    'transaction_id' => $uuid,
                    'provider' => 'marzpay',
                    'provider_reference' => $uuid,
                    'marzpay_uuid' => $uuid,
                    'country' => 'UG',
                    'description' => 'Appointment payment cancelled - ' . ($appointment ? $appointment->id : 'unknown'),
                    'transaction_type' => 'collection',
                    'webhook_event_type' => 'collection.cancelled',
                    'collection_data' => $transaction,
                    'processed_at' => now(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Handle Cancelled Collection Error: ' . $e->getMessage());
        }
    }

    /**
     * Validate webhook request for basic security
     */
    private function validateWebhookRequest(Request $request)
    {
        // Check if request is from allowed IPs (if MarzPay provides IP ranges)
        $allowedIps = config('services.marzpay.allowed_ips', []);
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
            Log::warning('Webhook request from unauthorized IP: ' . $request->ip());
            return false;
        }

        // Check content type
        if ($request->header('Content-Type') !== 'application/json') {
            Log::warning('Invalid content type for webhook: ' . $request->header('Content-Type'));
            return false;
        }

        // Check if payload is valid JSON
        if (!$request->isJson()) {
            Log::warning('Webhook payload is not valid JSON');
            return false;
        }

        return true;
    }

    /**
     * Verify webhook signature (placeholder for future implementation)
     * TODO: Implement when MarzPay provides signature verification details
     */
    private function verifyWebhookSignature($payload, $signature)
    {
        // Placeholder for signature verification
        // MarzPay may provide HMAC signature verification in the future
        $webhookSecret = config('services.marzpay.webhook_secret');

        if (!$webhookSecret) {
            Log::warning('Webhook secret not configured - signature verification skipped');
            return true; // Allow webhooks if secret not configured (for development)
        }

        // TODO: Implement actual signature verification
        // Example implementation (adjust based on MarzPay's signature method):
        // $expectedSignature = hash_hmac('sha256', json_encode($payload), $webhookSecret);
        // return hash_equals($expectedSignature, $signature);

        return true; // Placeholder - allow all for now
    }
}