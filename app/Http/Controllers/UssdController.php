<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\AppointmentPayments;
use App\Services\PatientMessenger;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * USSD menu for feature phones (Africa's Talking callback). The reply starts with
 * CON to keep the session open or END to close it. All state is in the "text"
 * field, which holds the digits pressed so far, joined with *.
 */
class UssdController extends Controller
{
    public function __construct(
        private SmsService $sms,
        private AppointmentPayments $payments,
        private PatientMessenger $messenger,
    ) {
    }

    public function handle(Request $request)
    {
        $secret = config('services.ussd.secret');
        if (!$secret || !hash_equals((string) $secret, (string) $request->query('token'))) {
            abort(403);
        }

        $phone = $this->sms->normalizeUgandanNumber((string) $request->input('phoneNumber'));
        $steps = $request->input('text') === null || $request->input('text') === '' ? [] : explode('*', (string) $request->input('text'));

        $patients = $phone ? $this->patientsFor($phone) : collect();
        if ($patients->isEmpty()) {
            return $this->end('This number is not registered. Please ask your clinic to add it.');
        }

        return match ($steps[0] ?? null) {
            null => $this->con("Easemed\n1 My visits\n2 Pay for a visit\n3 Medicine delivery\n4 Text me my link"),
            '1' => $this->visits($patients),
            '2' => $this->pay($patients, array_slice($steps, 1), $phone),
            '3' => $this->medicine($patients),
            '4' => $this->link($patients->first(), $phone),
            default => $this->end('Invalid choice.'),
        };
    }

    private function visits($patients)
    {
        $rows = Appointment::whereIn('patient_id', $patients->pluck('id'))
            ->where('status', '!=', 'cancelled')->where('appointment_time', '>=', now()->subHours(1))
            ->with('doctor')->orderBy('appointment_time')->limit(3)->get();

        if ($rows->isEmpty()) {
            return $this->end('You have no upcoming visits.');
        }

        return $this->end($rows->map(fn ($a, $i) => ($i + 1) . '. ' . $a->appointment_time->format('D j M g:iA')
            . ' Dr ' . $a->doctor->name . ' - ' . str_replace('_', ' ', $a->status))->implode("\n"));
    }

    private function unpaid($patients)
    {
        return Appointment::whereIn('patient_id', $patients->pluck('id'))
            ->where('status', 'awaiting_payment')->where('coverage_type', 'self_pay')->where('appointment_time', '>=', now())
            ->with('doctor')->orderBy('appointment_time')->limit(3)->get();
    }

    private function pay($patients, array $steps, string $phone)
    {
        $rows = $this->unpaid($patients);
        if ($rows->isEmpty()) {
            return $this->end('You have nothing to pay right now.');
        }

        if (!isset($steps[0])) {
            return $this->con("Pay which visit?\n" . $rows->map(fn ($a, $i) => ($i + 1) . ' ' . $a->appointment_time->format('D j M g:iA')
                . ' UGX ' . number_format($this->payments->amountFor($a)))->implode("\n"));
        }

        $appointment = $rows[(int) $steps[0] - 1] ?? null;
        if (!$appointment) {
            return $this->end('Invalid choice.');
        }

        if (!isset($steps[1])) {
            return $this->con('Pay UGX ' . number_format($this->payments->amountFor($appointment)) . " from {$phone}?\n1 Yes\n2 No");
        }

        if ($steps[1] !== '1') {
            return $this->end('Cancelled. You have not been charged.');
        }

        $outcome = $this->payments->requestMobileMoney($appointment, $phone);

        return $this->end($outcome['success'] ? 'Payment request sent. Approve it on your phone.' : 'We could not start the payment. Try again later.');
    }

    private function medicine($patients)
    {
        $rows = Prescription::whereIn('patient_id', $patients->pluck('id'))->whereNotNull('delivery_status')
            ->with('items')->latest()->limit(3)->get();

        if ($rows->isEmpty()) {
            return $this->end('You have no medicine orders.');
        }

        return $this->end($rows->map(fn ($rx) => ($rx->items->first()->name ?? 'Medicine') . ': ' . str_replace('_', ' ', $rx->delivery_status))->implode("\n"));
    }

    private function link(Patient $patient, string $phone)
    {
        $url = URL::temporarySignedRoute('patient.visits', now()->addHours(24), ['patient' => $patient->id]);
        $this->messenger->send($phone, 'Your Easemed visits: ' . $url);

        return $this->end('We sent a link to your phone.');
    }

    private function patientsFor(string $phone)
    {
        $local = '0' . substr($phone, 4);
        $plain = substr($phone, 1);

        return Patient::whereIn('contact_number', [$phone, $plain, $local])
            ->orWhereIn('parent_contact', [$phone, $plain, $local])->get();
    }

    private function con(string $text)
    {
        return response('CON ' . $text, 200)->header('Content-Type', 'text/plain');
    }

    private function end(string $text)
    {
        return response('END ' . $text, 200)->header('Content-Type', 'text/plain');
    }
}
