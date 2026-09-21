<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksStaffAccess;
use App\Models\AuditLog;
use App\Models\Prescription;
use App\Services\PatientNotifier;
use App\Services\PharmacyBilling;
use Illuminate\Http\Request;

/** Pharmacy desk: price a prescription, record the insurer's decision, and export insurer claims. */
class PharmacyController extends Controller
{
    use ChecksStaffAccess;

    public function __construct(private PharmacyBilling $billing, private PatientNotifier $notifier)
    {
    }

    public function price(Request $request, Prescription $prescription)
    {
        $user = $this->staff($request);
        abort_unless($this->canSeePatient($user, $prescription->patient), 404);

        $data = $request->validate([
            'coverage' => 'required|in:self_pay,insurance',
            'member_policy_id' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.unit_price' => 'required|numeric|min:0|max:100000000',
        ]);

        $prices = collect($data['items'])->pluck('unit_price', 'id')->all();

        try {
            $rx = $this->billing->price($prescription, $prices, $data['coverage'], $data['member_policy_id'] ?? null);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        AuditLog::record($user, 'pharmacy.priced', $rx);
        if ($rx->coverage_type === 'self_pay') {
            $this->notifier->sendMedicineQuote($rx->load('patient'));
        }

        return response()->json(['success' => true, 'prescription' => $rx]);
    }

    public function insurerDecision(Request $request, Prescription $prescription)
    {
        $user = $this->staff($request);
        abort_unless($this->canSeePatient($user, $prescription->patient), 404);

        $data = $request->validate([
            'result' => 'required|in:approved,declined',
            'insurer_amount' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:64',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $rx = $this->billing->decideInsurance($prescription, $data['result'], isset($data['insurer_amount']) ? (float) $data['insurer_amount'] : null, $data['reference'] ?? null, $data['note'] ?? null);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getMessage() === 'This prescription is not waiting for an insurer decision.' ? 409 : 422);
        }

        AuditLog::record($user, 'pharmacy.insurer.' . $data['result'], $rx);
        $this->notifier->sendMedicineQuote($rx->load('patient'));

        return response()->json(['success' => true, 'prescription' => $rx]);
    }

    /** CSV of insurer shares that were approved and whose patient share is settled. ?all=1 includes submitted ones. */
    public function claimsCsv(Request $request)
    {
        $user = $this->staff($request);
        $rows = $this->claimsQuery($user, $request->boolean('all'))
            ->with(['patient', 'memberPolicy.insurer', 'items'])->orderBy('id')->get();
        AuditLog::record($user, 'pharmacy.claims.export');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['prescription_id', 'insurer', 'member_number', 'insurer_reference', 'patient', 'items', 'total_ugx', 'insurer_share_ugx', 'patient_share_ugx', 'status']);
            foreach ($rows as $rx) {
                fputcsv($out, [
                    $rx->id, $rx->memberPolicy?->insurer?->name, $rx->memberPolicy?->member_number, $rx->insurer_reference,
                    $rx->patient?->name, $rx->items->map(fn ($i) => $i->name . ' x' . max(1, (int) $i->quantity))->implode('; '),
                    $rx->total_amount, $rx->insurer_amount, $rx->patient_amount, $rx->insurance_status,
                ]);
            }
            fclose($out);
        }, 'pharmacy-claims-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function claimsSubmitted(Request $request)
    {
        $user = $this->staff($request);
        $data = $request->validate(['prescription_ids' => 'required|array|min:1', 'prescription_ids.*' => 'integer']);

        $lifecycle = app(\App\Services\ClaimLifecycle::class);
        $count = 0;
        foreach ($this->claimsQuery($user, false)->whereIn('id', $data['prescription_ids'])->get() as $rx) {
            $lifecycle->submit($rx);
            $count++;
        }
        AuditLog::record($user, 'pharmacy.claims.submitted');

        return response()->json(['success' => true, 'submitted' => $count]);
    }

    private function claimsQuery(array $user, bool $includeSubmitted)
    {
        return Prescription::query()
            ->where('coverage_type', 'insurance')
            ->where('payment_status', 'paid')
            ->where('insurer_amount', '>', 0)
            ->where(fn ($q) => $q->whereNull('delivery_status')->orWhere('delivery_status', '!=', 'cancelled'))
            ->whereIn('insurance_status', $includeSubmitted ? ['approved', 'submitted'] : ['approved'])
            ->whereHas('patient', function ($q) use ($user) {
                if ($user['type'] === 'health_facility') {
                    $q->where(fn ($q) => $q->where('health_facility_id', $user['id'])->orWhereHas('healthFacilities', fn ($h) => $h->whereKey($user['id'])));
                } else {
                    $q->whereHas('appointments', fn ($a) => $a->where('doctor_id', $user['id']));
                }
            });
    }
}
