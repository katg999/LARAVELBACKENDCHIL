<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Wallet;
use App\Services\AppointmentPayments;
use App\Services\PatientNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * Paying for a visit from the patient's signed link. Whoever holds the link can pay,
 * so a relative can settle the bill with their own mobile money number.
 */
class VisitPaymentController extends Controller
{
    public function __construct(private AppointmentPayments $payments, private PatientNotifier $notifier)
    {
    }

    public function mobileMoney(Request $request, Appointment $appointment)
    {
        if (!$this->payable($appointment)) {
            return $this->back($appointment, 'This visit does not need a payment right now.');
        }

        $data = $request->validate(['phone' => 'required|string|max:20']);
        $outcome = $this->payments->requestMobileMoney($appointment, $data['phone']);

        return $this->back($appointment, $outcome['message']);
    }

    public function wallet(Appointment $appointment)
    {
        $appointment->loadMissing('patient');

        if (!$this->payable($appointment)) {
            return $this->back($appointment, 'This visit does not need a payment right now.');
        }

        $amount = $this->payments->amountFor($appointment);
        $wallet = Wallet::forPatient($appointment->patient);

        try {
            DB::transaction(function () use ($wallet, $amount, $appointment) {
                $wallet->debit($amount, 'Visit ' . $appointment->id, $appointment->id);
                $appointment->update(['status' => 'confirmed', 'payment_status' => 'completed', 'payment_method' => 'wallet']);
            });
        } catch (\RuntimeException $e) {
            return $this->back($appointment, 'Your wallet balance is too low for this visit.');
        }

        AuditLog::record(['type' => 'patient', 'id' => $appointment->patient_id], 'visit.paid.wallet', $appointment);
        $this->notifier->sendJoinLink($appointment->fresh(['patient', 'doctor']));
        app(\App\Services\DoctorNotifier::class)->appointmentConfirmed($appointment->fresh(['patient', 'doctor']));

        return $this->back($appointment, 'Paid from your wallet. Your visit is confirmed.');
    }

    private function payable(Appointment $appointment): bool
    {
        return $appointment->status === 'awaiting_payment' && $appointment->coverage_type === 'self_pay';
    }

    private function back(Appointment $appointment, string $message)
    {
        return redirect(URL::temporarySignedRoute('visit.show', now()->addHours(2), ['appointment' => $appointment->id]))
            ->with('status', $message);
    }
}
