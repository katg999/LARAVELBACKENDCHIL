<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Support\Facades\URL;

/**
 * Patient-facing pages reached through signed links (sent by SMS), so patients
 * need no password. They show appointment details and reveal the private video
 * room only inside the joining window of a paid, confirmed appointment.
 */
class PatientVisitController extends Controller
{
    /** Minutes before the start time that joining opens. */
    private const OPENS_BEFORE_MIN = 15;

    /** Minutes after the scheduled end that joining stays open. */
    private const CLOSES_AFTER_MIN = 30;

    public function show(Appointment $appointment)
    {
        $appointment->loadMissing(['patient', 'doctor', 'duration']);

        return view('visit.show', $this->visitState($appointment));
    }

    public function visits(Patient $patient)
    {
        $appointments = $patient->appointments()
            ->with(['doctor', 'duration'])
            ->orderByDesc('appointment_time')
            ->limit(50)
            ->get()
            ->map(fn (Appointment $a) => [
                'appointment' => $a,
                'state' => $this->visitState($a)['state'],
                'link' => URL::temporarySignedRoute('visit.show', now()->addDay(), ['appointment' => $a->id]),
            ]);

        return view('visit.list', ['patient' => $patient, 'items' => $appointments]);
    }

    /** @return array{appointment: Appointment, state: string, joinUrl: ?string, opensAt: \Illuminate\Support\Carbon, closesAt: \Illuminate\Support\Carbon} */
    private function visitState(Appointment $appointment): array
    {
        $minutes = $appointment->duration->minutes ?? 30;
        $opensAt = $appointment->appointment_time->copy()->subMinutes(self::OPENS_BEFORE_MIN);
        $closesAt = $appointment->appointment_time->copy()->addMinutes($minutes + self::CLOSES_AFTER_MIN);

        if ($appointment->status === 'cancelled') {
            $state = 'cancelled';
        } elseif ($appointment->status !== 'confirmed' || $appointment->payment_status === 'failed') {
            $state = 'awaiting_payment';
        } elseif (now()->lt($opensAt)) {
            $state = 'early';
        } elseif (now()->gt($closesAt)) {
            $state = 'ended';
        } else {
            $state = 'open';
        }

        return [
            'appointment' => $appointment,
            'state' => $state,
            // The room address is only handed out while joining is open.
            'joinUrl' => $state === 'open' ? $appointment->meeting_url : null,
            'opensAt' => $opensAt,
            'closesAt' => $closesAt,
        ];
    }
}
