<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Insurer;
use App\Models\MemberPolicy;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Patients send their own insurance details (from their signed link or after signing in),
 * so the clinic does not have to key them in. Staff review each one before it can be used to book.
 */
class PatientInsuranceController extends Controller
{
    /** From the signed link texted to the patient. */
    public function store(Request $request, Patient $patient)
    {
        $result = $this->submit($request, $patient);

        return redirect(URL::temporarySignedRoute('patient.visits', now()->addHours(4), ['patient' => $patient->id]))
            ->with('insurance_status', $result);
    }

    /** From the signed-in portal; only for patients in that login. */
    public function portalStore(Request $request, Patient $patient)
    {
        $auth = $request->session()->get('patient_auth');
        abort_unless($auth && in_array($patient->id, $auth['patient_ids'], true), 403);

        return redirect()->route('patient.records')->with('insurance_status', $this->submit($request, $patient));
    }

    private function submit(Request $request, Patient $patient): string
    {
        $data = $request->validate([
            'insurer_id' => 'required|exists:insurers,id',
            'member_number' => 'required|string|max:64',
            'scheme_name' => 'nullable|string|max:120',
            'card' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        abort_unless(Insurer::whereKey($data['insurer_id'])->where('active', true)->exists(), 422);

        $member = trim($data['member_number']);
        $existing = MemberPolicy::where('insurer_id', $data['insurer_id'])->where('member_number', $member)->first();

        if ($existing) {
            // Never reveal that someone else already uses this member number.
            return (int) $existing->patient_id === (int) $patient->id
                ? 'You already sent these details.'
                : 'We could not add those details. Please ask your clinic for help.';
        }

        $path = null;
        if ($request->hasFile('card')) {
            $file = $request->file('card');
            $path = $file->storeAs('insurance-cards/' . $patient->id, Str::uuid() . '.' . $file->getClientOriginalExtension(), 'local');
        }

        $policy = MemberPolicy::create([
            'patient_id' => $patient->id, 'insurer_id' => $data['insurer_id'], 'member_number' => $member,
            'scheme_name' => $data['scheme_name'] ?? null, 'status' => 'pending_review', 'submitted_by' => 'patient',
            'card_image_path' => $path,
        ]);
        AuditLog::record(['type' => 'patient', 'id' => $patient->id], 'insurance.policy.submitted', $policy);

        return 'Thank you. Your clinic will confirm your insurance and you will get a text.';
    }
}
