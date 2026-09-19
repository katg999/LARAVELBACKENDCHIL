<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksStaffAccess;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Services\PatientNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Staff side of prescriptions: doctors issue them, clinic staff and doctors review uploads and run delivery. */
class PrescriptionController extends Controller
{
    use ChecksStaffAccess;

    public function __construct(private PatientNotifier $notifier)
    {
    }

    /** A doctor writes a prescription for one of their own appointments. */
    public function issue(Request $request, Appointment $appointment)
    {
        $user = $this->staff($request);
        if ($user['type'] !== 'doctor' || (int) $appointment->doctor_id !== (int) $user['id']) {
            abort(403);
        }

        $data = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1|max:20',
            'items.*.name' => 'required|string|max:120',
            'items.*.dosage' => 'nullable|string|max:120',
            'items.*.quantity' => 'nullable|integer|min:1|max:1000',
            'items.*.instructions' => 'nullable|string|max:255',
        ]);

        $prescription = DB::transaction(function () use ($appointment, $data) {
            $p = Prescription::create([
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'source' => 'issued',
                'status' => 'issued',
                'notes' => $data['notes'] ?? null,
            ]);
            $p->items()->createMany($data['items']);

            return $p;
        });

        return response()->json(['success' => true, 'prescription' => $prescription->load('items')], 201);
    }

    /** Prescriptions the staff member may see, newest first. ?status= filters (default: waiting for review). */
    public function queue(Request $request)
    {
        $user = $this->staff($request);
        $status = $request->input('status', 'pending_review');

        $rows = $this->visible($user)
            ->where('status', $status)
            ->with(['patient:id,name,patient_id', 'items'])
            ->latest()
            ->limit(100)
            ->get();

        return response()->json($rows);
    }

    public function review(Request $request, Prescription $prescription)
    {
        $user = $this->staff($request);
        $this->authorizePrescription($user, $prescription);

        $data = $request->validate(['result' => 'required|in:approved,rejected', 'note' => 'nullable|string|max:500']);

        if ($prescription->status !== 'pending_review') {
            return response()->json(['success' => false, 'message' => 'This prescription is not waiting for review.'], 409);
        }

        $prescription->update(['status' => $data['result'], 'review_note' => $data['note'] ?? null]);
        $this->notifier->sendPrescriptionReviewed($prescription->fresh('patient'));

        return response()->json(['success' => true, 'status' => $prescription->status]);
    }

    public function updateDelivery(Request $request, Prescription $prescription)
    {
        $user = $this->staff($request);
        $this->authorizePrescription($user, $prescription);

        $data = $request->validate(['status' => 'required|in:preparing,out_for_delivery,delivered,cancelled']);
        $allowed = Prescription::DELIVERY_FLOW[$prescription->delivery_status] ?? [];

        if (!in_array($data['status'], $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot move delivery from '{$prescription->delivery_status}' to '{$data['status']}'.",
            ], 409);
        }

        $prescription->update(['delivery_status' => $data['status']]);
        $this->notifier->sendDeliveryUpdate($prescription->fresh('patient'));

        return response()->json(['success' => true, 'delivery_status' => $prescription->delivery_status]);
    }

    /** The uploaded prescription photo, for staff who may see this patient. */
    public function image(Request $request, Prescription $prescription)
    {
        $user = $this->staff($request);
        $this->authorizePrescription($user, $prescription);

        abort_unless($prescription->image_path && Storage::disk('local')->exists($prescription->image_path), 404);

        return Storage::disk('local')->response($prescription->image_path);
    }

    private function authorizePrescription(array $user, Prescription $prescription): void
    {
        abort_unless($this->canSeePatient($user, $prescription->patient), 404);
    }

    private function visible(array $user)
    {
        return Prescription::query()->whereHas('patient', function ($q) use ($user) {
            if ($user['type'] === 'health_facility') {
                $q->where(function ($q) use ($user) {
                    $q->where('health_facility_id', $user['id'])
                        ->orWhereHas('healthFacilities', fn ($h) => $h->whereKey($user['id']));
                });
            } else {
                $q->whereHas('appointments', fn ($a) => $a->where('doctor_id', $user['id']));
            }
        });
    }
}
