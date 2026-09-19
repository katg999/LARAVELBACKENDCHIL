<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksStaffAccess;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Wallet;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/** Staff tools for wallets and for sending a pay link to someone else (a relative, an employer contact). */
class WalletController extends Controller
{
    use ChecksStaffAccess;

    public function show(Request $request, Patient $patient)
    {
        $user = $this->staff($request);
        abort_unless($this->canSeePatient($user, $patient), 404);

        $wallet = Wallet::forPatient($patient);

        return response()->json(['balance' => $wallet->balance, 'transactions' => $wallet->transactions()->limit(50)->get()]);
    }

    /** Add money received another way, such as cash paid at the counter. */
    public function credit(Request $request, Patient $patient)
    {
        $user = $this->staff($request);
        abort_unless($this->canSeePatient($user, $patient), 404);

        $data = $request->validate(['amount' => 'required|numeric|min:1|max:5000000', 'note' => 'nullable|string|max:120']);
        $wallet = Wallet::forPatient($patient);
        $wallet->credit((float) $data['amount'], $data['note'] ?? 'Top-up at clinic');
        AuditLog::record($user, 'wallet.credited', $patient);

        return response()->json(['success' => true, 'balance' => $wallet->balance]);
    }

    /** Text the visit link to another phone so that person can pay. */
    public function sendPayLink(Request $request, Appointment $appointment, SmsService $sms)
    {
        $user = $this->staff($request);
        $this->scopeTo($user, Appointment::query()->whereKey($appointment->id))->firstOrFail();

        $data = $request->validate(['phone' => 'required|string|max:20']);
        $appointment->loadMissing(['doctor', 'patient']);

        $link = URL::temporarySignedRoute('visit.show', now()->addDays(3), ['appointment' => $appointment->id]);
        $sent = $sms->send($data['phone'], "Pay for {$appointment->patient->name}'s appointment with Dr. {$appointment->doctor->name} on "
            . $appointment->appointment_time->format('D j M, g:i A') . ': ' . $link);
        AuditLog::record($user, 'visit.paylink.sent', $appointment);

        return response()->json(['success' => true, 'sent' => $sent]);
    }
}
