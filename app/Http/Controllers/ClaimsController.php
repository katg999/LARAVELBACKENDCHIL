<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksStaffAccess;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Prescription;
use App\Services\AppointmentPayments;
use App\Services\ClaimLifecycle;
use Illuminate\Http\Request;

/**
 * Follow claims after the file has gone to the insurer: record the insurer's reply
 * (accepted, queried, rejected, paid) and see what is still outstanding.
 */
class ClaimsController extends Controller
{
    use ChecksStaffAccess;

    public function __construct(private ClaimLifecycle $lifecycle)
    {
    }

    public function visitResponse(Request $request, Appointment $appointment)
    {
        $user = $this->staff($request);
        $this->scopeTo($user, Appointment::query()->whereKey($appointment->id))->firstOrFail();

        return $this->respond($request, $user, $appointment, 'claim.visit');
    }

    public function medicineResponse(Request $request, Prescription $prescription)
    {
        $user = $this->staff($request);
        abort_unless($this->canSeePatient($user, $prescription->patient), 404);

        return $this->respond($request, $user, $prescription, 'claim.medicine');
    }

    /** What is outstanding, by status, with how long each open claim has been waiting. */
    public function index(Request $request, AppointmentPayments $payments)
    {
        $user = $this->staff($request);
        $data = $request->validate(['status' => 'nullable|in:submitted,queried,accepted,rejected,paid', 'overdue_days' => 'nullable|integer|min:1|max:365']);
        $overdueDays = (int) ($data['overdue_days'] ?? 30);

        $visits = $this->scopeTo($user, Appointment::query())->whereNotNull('claim_status')
            ->with(['patient:id,name', 'memberPolicy.insurer:id,name'])->get()
            ->map(fn (Appointment $a) => $this->row('visit', $a, $a->memberPolicy?->insurer?->name, $a->patient?->name, $payments->amountFor($a), $a->appointment_time));

        $medicine = Prescription::query()->whereNotNull('claim_status')
            ->whereHas('patient', fn ($q) => $this->restrictToVisiblePatients($q, $user))
            ->with(['patient:id,name', 'memberPolicy.insurer:id,name'])->get()
            ->map(fn (Prescription $p) => $this->row('medicine', $p, $p->memberPolicy?->insurer?->name, $p->patient?->name, (float) $p->insurer_amount, $p->created_at));

        $all = $visits->concat($medicine);
        if (isset($data['status'])) {
            $all = $all->where('status', $data['status']);
        }

        $summary = $visits->concat($medicine)->groupBy('status')->map(fn ($g) => [
            'count' => $g->count(),
            'expected_ugx' => round($g->sum('expected_ugx'), 2),
            'paid_ugx' => round($g->sum('paid_ugx'), 2),
        ]);

        $open = $all->filter(fn ($r) => in_array($r['status'], ClaimLifecycle::OPEN, true));

        return response()->json([
            'summary' => $summary,
            'outstanding_ugx' => round($visits->concat($medicine)->filter(fn ($r) => in_array($r['status'], ClaimLifecycle::OPEN, true))->sum('expected_ugx'), 2),
            'overdue_after_days' => $overdueDays,
            'overdue' => $open->filter(fn ($r) => $r['days_waiting'] !== null && $r['days_waiting'] >= $overdueDays)->sortByDesc('days_waiting')->values(),
            'claims' => $all->sortBy('submitted_at')->values(),
        ]);
    }

    private function respond(Request $request, array $user, $claim, string $auditPrefix)
    {
        $data = $request->validate([
            'status' => 'required|in:submitted,queried,accepted,rejected,paid',
            'reference' => 'nullable|string|max:64',
            'note' => 'nullable|string|max:1000',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        try {
            $updated = $this->lifecycle->respond($claim, $data['status'], [
                'reference' => $data['reference'] ?? null, 'note' => $data['note'] ?? null, 'amount_paid' => $data['amount_paid'] ?? null,
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        AuditLog::record($user, $auditPrefix . '.' . $data['status'], $updated);

        return response()->json(['success' => true, 'claim_status' => $updated->claim_status]);
    }

    private function row(string $type, $m, ?string $insurer, ?string $patient, float $expected, $date): array
    {
        $open = in_array($m->claim_status, ClaimLifecycle::OPEN, true);

        return [
            'type' => $type,
            'id' => $m->id,
            'insurer' => $insurer,
            'patient' => $patient,
            'date' => optional($date)->toDateString(),
            'status' => $m->claim_status,
            'insurer_claim_ref' => $m->claim_reference,
            'note' => $m->claim_note,
            'expected_ugx' => $expected,
            'paid_ugx' => (float) ($m->claim_paid_amount ?? 0),
            'submitted_at' => optional($m->claim_submitted_at)->toDateTimeString(),
            'days_waiting' => $open && $m->claim_submitted_at ? (int) $m->claim_submitted_at->diffInDays(now()) : null,
        ];
    }

    private function restrictToVisiblePatients($q, array $user)
    {
        if ($user['type'] === 'health_facility') {
            return $q->where(fn ($q) => $q->where('health_facility_id', $user['id'])->orWhereHas('healthFacilities', fn ($h) => $h->whereKey($user['id'])));
        }

        return $q->whereHas('appointments', fn ($a) => $a->where('doctor_id', $user['id']));
    }
}
