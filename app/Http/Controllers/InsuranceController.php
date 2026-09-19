<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksStaffAccess;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Insurer;
use App\Models\MemberPolicy;
use App\Models\Patient;
use App\Services\PatientNotifier;
use Illuminate\Http\Request;

/**
 * Staff-side insurance handling. Clinics and doctors register a patient's policy,
 * confirm or decline an insured booking, and export verified visit records for the
 * insurer (as CSV now, through Smart Access when an integration exists).
 */
class InsuranceController extends Controller
{
    use ChecksStaffAccess;

    public function __construct(private PatientNotifier $notifier)
    {
    }

    public function insurers()
    {
        return response()->json(Insurer::where('active', true)->orderBy('name')->get(['id', 'name', 'code']));
    }

    public function addPolicy(Request $request, Patient $patient)
    {
        $user = $this->staff($request);
        if (!$this->canSeePatient($user, $patient)) {
            abort(403);
        }

        $data = $request->validate([
            'insurer_id' => 'required|exists:insurers,id',
            'member_number' => 'required|string|max:64',
            'scheme_name' => 'nullable|string|max:120',
        ]);

        $policy = MemberPolicy::firstOrCreate(
            ['insurer_id' => $data['insurer_id'], 'member_number' => $data['member_number']],
            ['patient_id' => $patient->id, 'scheme_name' => $data['scheme_name'] ?? null]
        );

        if ($policy->patient_id !== $patient->id) {
            return response()->json(['success' => false, 'message' => 'That member number belongs to another patient.'], 422);
        }

        AuditLog::record($user, 'insurance.policy.added', $policy);

        return response()->json(['success' => true, 'policy' => $policy->load('insurer')], 201);
    }

    public function verify(Request $request, Appointment $appointment)
    {
        $user = $this->staff($request);
        $this->scopeTo($user, Appointment::query()->whereKey($appointment->id))->firstOrFail();

        $data = $request->validate([
            'result' => 'required|in:verified,rejected',
            'note' => 'nullable|string|max:500',
        ]);

        if ($appointment->coverage_type !== 'insurance' || $appointment->insurance_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This appointment is not waiting for an insurance check.'], 409);
        }

        if ($data['result'] === 'verified') {
            $appointment->update([
                'status' => 'confirmed',
                'payment_status' => 'insurance',
                'payment_method' => 'insurance',
                'insurance_status' => 'verified',
                'insurance_note' => $data['note'] ?? null,
            ]);
            $appointment->memberPolicy?->update(['verified_at' => now()]);
            $this->notifier->sendJoinLink($appointment->fresh(['patient', 'doctor']));
            app(\App\Services\DoctorNotifier::class)->appointmentConfirmed($appointment->fresh(['patient', 'doctor']));
        } else {
            // Declined: fall back to normal self-pay so the patient can still keep the booking.
            $appointment->update([
                'status' => 'awaiting_payment',
                'coverage_type' => 'self_pay',
                'insurance_status' => 'rejected',
                'insurance_note' => $data['note'] ?? null,
            ]);
            $this->notifier->sendInsuranceDeclined($appointment->fresh(['patient']));
        }

        AuditLog::record($user, 'insurance.' . $data['result'], $appointment);

        return response()->json(['success' => true, 'status' => $appointment->status, 'insurance_status' => $appointment->insurance_status]);
    }

    /** CSV of verified insured visits not yet handed to the insurer. ?all=1 includes submitted ones. */
    public function exportCsv(Request $request)
    {
        $user = $this->staff($request);
        $query = $this->scopeTo($user, Appointment::query())
            ->where('coverage_type', 'insurance')
            ->whereIn('insurance_status', $request->boolean('all') ? ['verified', 'submitted'] : ['verified'])
            ->with(['patient', 'doctor', 'duration', 'memberPolicy.insurer'])
            ->orderBy('appointment_time');

        if ($request->filled('insurer_id')) {
            $query->whereHas('memberPolicy', fn ($q) => $q->where('insurer_id', $request->input('insurer_id')));
        }
        if ($request->filled('from')) {
            $query->whereDate('appointment_time', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('appointment_time', '<=', $request->input('to'));
        }

        $rows = $query->get();
        AuditLog::record($user, 'insurance.export');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['appointment_id', 'insurer', 'member_number', 'visit_code', 'patient', 'doctor', 'visit_date', 'minutes', 'reason', 'status']);
            foreach ($rows as $a) {
                fputcsv($out, [
                    $a->id,
                    $a->memberPolicy?->insurer?->name,
                    $a->memberPolicy?->member_number,
                    $a->visit_code,
                    $a->patient?->name,
                    $a->doctor?->name,
                    $a->appointment_time->toDateTimeString(),
                    $a->duration?->minutes,
                    $a->reason,
                    $a->insurance_status,
                ]);
            }
            fclose($out);
        }, 'insured-visits-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** Mark exported records as handed to the insurer so they are not exported twice. */
    public function markSubmitted(Request $request)
    {
        $user = $this->staff($request);
        $data = $request->validate(['appointment_ids' => 'required|array|min:1', 'appointment_ids.*' => 'integer']);

        $count = $this->scopeTo($user, Appointment::query())
            ->whereIn('id', $data['appointment_ids'])
            ->where('insurance_status', 'verified')
            ->update(['insurance_status' => 'submitted']);

        AuditLog::record($user, 'insurance.submitted');

        return response()->json(['success' => true, 'submitted' => $count]);
    }
}
