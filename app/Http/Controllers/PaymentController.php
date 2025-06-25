<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Appointment;
use App\Models\Student;
use App\Models\Doctor;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function index()
    {
        return view('payment');
    }

    public function createAppointmentCheckout(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'doctor_id' => 'required|exists:doctors,id',
            'duration' => 'required|in:15,20,30,45,60',
            'appointment_time' => 'required|date',
            'reason' => 'required|string',
            'school_id' => 'required'
        ]);

        // Calculate amount based on duration
        $amount = $this->calculateAmount($validated['duration']);
        
        // Get student and doctor details for display
        $student = Student::find($validated['student_id']);
        $doctor = Doctor::find($validated['doctor_id']);

        try {
            // Create appointment record with pending status
            $appointment = Appointment::create([
                'student_id' => $validated['student_id'],
                'doctor_id' => $validated['doctor_id'],
                'school_id' => $validated['school_id'],
                'appointment_time' => $validated['appointment_time'],
                'duration' => $validated['duration'],
                'reason' => $validated['reason'],
                'amount' => $amount,
                'status' => 'pending_payment'
            ]);

            // Create Stripe checkout session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'ugx', // Uganda Shillings
                        'product_data' => [
                            'name' => "Doctor Appointment - {$student->name}",
                            'description' => "Appointment with Dr. {$doctor->name} for {$validated['duration']} minutes",
                        ],
                        'unit_amount' => $amount, // Amount is already in the smallest currency unit
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.appointment.success', ['appointment' => $appointment->id]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.appointment.cancel', ['appointment' => $appointment->id]),
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'student_name' => $student->name,
                    'doctor_name' => $doctor->name,
                    'duration' => $validated['duration']
                ]
            ]);

            // Store session ID in appointment
            $appointment->update(['stripe_session_id' => $session->id]);

            return response()->json([
                'success' => true,
                'checkout_url' => $session->url,
                'appointment_id' => $appointment->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to create payment session: ' . $e->getMessage()
            ], 500);
        }
    }

    public function appointmentSuccess(Request $request, $appointmentId)
    {
        $appointment = Appointment::find($appointmentId);
        
        if (!$appointment) {
            return redirect()->route('school.dashboard')->with('error', 'Appointment not found');
        }

        // Verify the payment with Stripe
        $sessionId = $request->get('session_id');
        if ($sessionId) {
            try {
                $session = Session::retrieve($sessionId);
                
                if ($session->payment_status === 'paid') {
                    $appointment->update([
                        'status' => 'confirmed',
                        'payment_status' => 'paid',
                        'stripe_payment_id' => $session->payment_intent
                    ]);
                    
                    return view('payment.success', compact('appointment'));
                }
            } catch (\Exception $e) {
                // Log error but still show success page
                \Log::error('Stripe session verification failed: ' . $e->getMessage());
            }
        }

        return view('payment.success', compact('appointment'));
    }

    public function appointmentCancel($appointmentId)
    {
        $appointment = Appointment::find($appointmentId);
        
        if ($appointment) {
            $appointment->update(['status' => 'cancelled']);
        }

        return view('payment.cancel', compact('appointment'));
    }

    private function calculateAmount($duration)
    {
        // Pricing in UGX (smallest unit - no decimals for UGX)
        $pricing = [
            15 => 30000,  // 30,000 UGX for 15 minutes
            20 => 45000,  // 45,000 UGX for 20 minutes
            30 => 60000,  // 60,000 UGX for 30 minutes
            45 => 80000,  // 80,000 UGX for 45 minutes
            60 => 100000, // 100,000 UGX for 60 minutes
        ];

        return $pricing[$duration] ?? 30000; // Default to 30,000 if duration not found
    }

    // Keep existing methods for backward compatibility
    public function checkout(Request $request)
    {
        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Laravel Stripe Payment',
                        ],
                        'unit_amount' => 1000, // $10.00 in cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success'),
                'cancel_url' => route('payment.cancel'),
            ]);

            return redirect($session->url, 303);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Unable to create payment session: ' . $e->getMessage()]);
        }
    }
}