<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Prescription;
use App\Services\PharmacyBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/** Prescription actions a patient takes from their signed link: upload a photo, ask for delivery. */
class PatientPrescriptionController extends Controller
{
    /** Medicine-only request: a photo of a prescription from another doctor, held for staff review. */
    public function upload(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'photo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        $file = $request->file('photo');
        $path = $file->storeAs('prescriptions/' . $patient->id, Str::uuid() . '.' . $file->getClientOriginalExtension(), 'local');

        Prescription::create([
            'patient_id' => $patient->id,
            'source' => 'uploaded',
            'status' => 'pending_review',
            'notes' => $data['notes'] ?? null,
            'image_path' => $path,
        ]);

        return $this->back($patient, 'Prescription sent. A clinician will review it and you will get a text.');
    }

    public function requestDelivery(Request $request, Patient $patient, Prescription $prescription)
    {
        abort_unless((int) $prescription->patient_id === (int) $patient->id, 404);

        $data = $request->validate([
            'address' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        if (!$prescription->canRequestDelivery()) {
            return $this->back($patient, $prescription->payment_status === 'paid'
                ? 'Delivery cannot be requested for this prescription.'
                : 'Your medicine must be priced and paid for before it can be delivered.');
        }

        $prescription->update([
            'delivery_status' => 'requested',
            'delivery_address' => $data['address'],
            'delivery_phone' => $data['phone'] ?? $patient->contact_number,
            'delivery_requested_at' => now(),
        ]);

        return $this->back($patient, 'Delivery requested. We will text you as it moves.');
    }

    public function payMobileMoney(Request $request, Patient $patient, Prescription $prescription, PharmacyBilling $billing)
    {
        abort_unless((int) $prescription->patient_id === (int) $patient->id, 404);
        $data = $request->validate(['phone' => 'required|string|max:20']);

        return $this->back($patient, $billing->requestMobileMoney($prescription, $data['phone'])['message']);
    }

    public function payWallet(Patient $patient, Prescription $prescription, PharmacyBilling $billing)
    {
        abort_unless((int) $prescription->patient_id === (int) $patient->id, 404);

        try {
            $billing->payFromWallet($prescription);
        } catch (\RuntimeException $e) {
            return $this->back($patient, 'Your wallet balance is too low for this payment.');
        } catch (\DomainException $e) {
            return $this->back($patient, $e->getMessage());
        }

        return $this->back($patient, 'Paid from your wallet. You can now request delivery.');
    }

    private function back(Patient $patient, string $message)
    {
        return redirect(URL::temporarySignedRoute('patient.visits', now()->addHours(4), ['patient' => $patient->id]))
            ->with('status', $message);
    }
}
