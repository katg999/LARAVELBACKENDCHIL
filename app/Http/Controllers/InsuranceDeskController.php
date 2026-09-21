<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksStaffAccess;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\HealthFacility;
use App\Models\Duration;
use App\Models\MemberPolicy;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\AppointmentPayments;
use App\Services\ClaimLifecycle;
use Illuminate\Http\Request;

/**
 * One screen for staff to run the whole insurance flow: confirm what patients send, verify insured visits,
 * price medicine and record the insurer's answer, then send and track claims. It uses the same endpoints
 * as the JSON API, so nothing here bypasses their rules.
 */
class InsuranceDeskController extends Controller
{
    use ChecksStaffAccess;

    public function index(Request $request, AppointmentPayments $payments)
    {
        $user = $this->staff($request);
        $visible = fn ($q) => $this->restrictToVisiblePatients($q, $user);
        $appointments = fn () => $this->scopeTo($user, Appointment::query());
        $prescriptions = fn () => Prescription::query()->whereHas('patient', $visible);

        $pendingPolicies = MemberPolicy::where('status', 'pending_review')->whereHas('patient', $visible)
            ->with(['patient:id,name', 'insurer:id,name'])->latest()->get();

        $toVerify = $appointments()->where('coverage_type', 'insurance')->where('status', 'awaiting_verification')
            ->with(['patient:id,name', 'doctor:id,name', 'memberPolicy.insurer:id,name'])->orderBy('appointment_time')->get();

        $toPrice = $prescriptions()->whereIn('status', ['issued', 'approved'])->whereNull('total_amount')->whereNull('delivery_status')
            ->with(['items', 'patient.policies' => fn ($q) => $q->where('status', 'active')->with('insurer:id,name')])->latest()->get();

        $awaitingInsurer = $prescriptions()->where('coverage_type', 'insurance')->where('insurance_status', 'pending')
            ->with(['items', 'patient:id,name', 'memberPolicy.insurer:id,name'])->latest()->get();

        $readyVisits = $appointments()->where('coverage_type', 'insurance')->where('insurance_status', 'verified')->attended()
            ->with(['patient:id,name', 'memberPolicy.insurer:id,name'])->orderBy('appointment_time')->get();

        $readyMedicine = $prescriptions()->where('coverage_type', 'insurance')->where('payment_status', 'paid')->where('insurer_amount', '>', 0)
            ->where('insurance_status', 'approved')->where(fn ($q) => $q->whereNull('delivery_status')->orWhere('delivery_status', '!=', 'cancelled'))
            ->with(['patient:id,name', 'memberPolicy.insurer:id,name'])->get();

        $tracked = $appointments()->whereNotNull('claim_status')->with(['patient:id,name', 'memberPolicy.insurer:id,name'])->get()
            ->map(fn (Appointment $a) => $this->row('visit', $a, $a->patient?->name, $a->memberPolicy?->insurer?->name, $payments->amountFor($a)))
            ->concat($prescriptions()->whereNotNull('claim_status')->with(['patient:id,name', 'memberPolicy.insurer:id,name'])->get()
                ->map(fn (Prescription $p) => $this->row('medicine', $p, $p->patient?->name, $p->memberPolicy?->insurer?->name, (float) $p->insurer_amount)))
            ->sortBy('submitted')->values();

        // Patients with confirmed cover, so staff can book an insured visit
        $bookable = Patient::whereHas('policies', fn ($q) => $q->where('status', 'active'))->where(fn ($q) => $visible($q))
            ->with(['policies' => fn ($q) => $q->where('status', 'active')->with('insurer:id,name')])->orderBy('name')->limit(200)->get();

        return view('insurance.desk', [
            'bookable' => $bookable,
            'doctors' => Doctor::orderBy('name')->limit(100)->get(['id', 'name', 'specialization']),
            'durations' => Duration::where('is_active', true)->orderBy('minutes')->get(['id', 'minutes', 'duration_type']),
            'facilityId' => $user['type'] === 'health_facility' ? $user['id'] : null,
            'healthFacility' => $user['type'] === 'health_facility' ? HealthFacility::find($user['id']) : null,
            'doctor' => $user['type'] === 'doctor' ? Doctor::find($user['id']) : null,
            'pendingPolicies' => $pendingPolicies,
            'toVerify' => $toVerify,
            'toPrice' => $toPrice,
            'awaitingInsurer' => $awaitingInsurer,
            'readyVisits' => $readyVisits,
            'readyMedicine' => $readyMedicine,
            'tracked' => $tracked,
            'open' => ClaimLifecycle::OPEN,
        ]);
    }

    private function row(string $type, $m, ?string $patient, ?string $insurer, float $expected): array
    {
        $open = in_array($m->claim_status, ClaimLifecycle::OPEN, true);

        return [
            'type' => $type, 'id' => $m->id, 'patient' => $patient, 'insurer' => $insurer, 'status' => $m->claim_status,
            'ref' => $m->claim_reference, 'note' => $m->claim_note, 'expected' => $expected, 'paid' => (float) ($m->claim_paid_amount ?? 0),
            'submitted' => optional($m->claim_submitted_at)->toDateTimeString(),
            'days' => $open && $m->claim_submitted_at ? (int) $m->claim_submitted_at->diffInDays(now()) : null,
            'url' => $type === 'visit' ? route('insurance.claims.visit', $m->id) : route('insurance.claims.medicine', $m->id),
        ];
    }
}
