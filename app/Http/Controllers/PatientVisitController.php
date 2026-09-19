<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Consent;
use Illuminate\Http\Request;
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

    /** Record the patient's consent, then send them into the private room (audio only if asked). */
    public function join(Request $request, Appointment $appointment)
    {
        $appointment->loadMissing(['patient', 'doctor', 'duration']);
        $state = $this->visitState($appointment);

        if ($state['state'] !== 'open') {
            return redirect(URL::temporarySignedRoute('visit.show', now()->addHour(), ['appointment' => $appointment->id]));
        }

        Consent::firstOrCreate(
            ['patient_id' => $appointment->patient_id, 'appointment_id' => $appointment->id, 'type' => 'video_visit'],
            ['ip' => $request->ip(), 'granted_at' => now()]
        );
        AuditLog::record(['type' => 'patient', 'id' => $appointment->patient_id], 'visit.joined', $appointment);

        $url = $appointment->meeting_url;
        if ($request->boolean('audio_only')) {
            $url .= '#config.startWithVideoMuted=true&config.startAudioOnly=true';
        }

        return redirect()->away($url);
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

        $prescriptions = $patient->prescriptions()->with('items')->latest()->limit(20)->get()->map(fn ($p) => [
            'prescription' => $p,
            'deliveryUrl' => $p->canRequestDelivery()
                ? URL::temporarySignedRoute('patient.prescriptions.delivery', now()->addHours(4), ['patient' => $patient->id, 'prescription' => $p->id])
                : null,
        ]);

        return view('visit.list', [
            'patient' => $patient,
            'items' => $appointments,
            'prescriptions' => $prescriptions,
            'uploadUrl' => URL::temporarySignedRoute('patient.prescriptions.upload', now()->addHours(4), ['patient' => $patient->id]),
        ]);
    }

    /** @return array{appointment: Appointment, state: string, joinUrl: ?string, opensAt: \Illuminate\Support\Carbon, closesAt: \Illuminate\Support\Carbon} */
    private function visitState(Appointment $appointment): array
    {
        $minutes = $appointment->duration->minutes ?? 30;
        $opensAt = $appointment->appointment_time->copy()->subMinutes(self::OPENS_BEFORE_MIN);
        $closesAt = $appointment->appointment_time->copy()->addMinutes($minutes + self::CLOSES_AFTER_MIN);

        if ($appointment->status === 'cancelled') {
            $state = 'cancelled';
        } elseif ($appointment->status === 'awaiting_verification') {
            $state = 'awaiting_verification';
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
            // The room address is never in the page: joining goes through visit.join, which records consent.
            'joinUrl' => $state === 'open'
                ? URL::temporarySignedRoute('visit.join', now()->addMinutes(30), ['appointment' => $appointment->id])
                : null,
            'opensAt' => $opensAt,
            'closesAt' => $closesAt,
        ];
    }
}
