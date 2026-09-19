<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Employer;
use App\Models\Patient;
use App\Services\AppointmentPayments;
use Illuminate\Http\Request;

/** Employer accounts: a company pays for its staff's visits, up to a monthly limit, and gets a monthly invoice. Admin only. */
class EmployerController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'contact_phone' => 'nullable|string|max:20',
            'monthly_cap' => 'nullable|numeric|min:0',
        ]);

        return response()->json(Employer::create($data), 201);
    }

    public function addMember(Request $request, Employer $employer)
    {
        $data = $request->validate(['patient_id' => 'required|exists:patients,id']);
        $employer->members()->syncWithoutDetaching([$data['patient_id']]);

        return response()->json(['success' => true, 'members' => $employer->members()->count()]);
    }

    public function invoice(Request $request, Employer $employer, AppointmentPayments $payments)
    {
        $month = $request->input('month', now()->format('Y-m'));
        abort_unless(preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month), 422);

        $appointments = $employer->appointments()
            ->whereIn('status', ['confirmed', 'awaiting_approval', 'completed'])
            ->whereBetween('appointment_time', [
                \Carbon\Carbon::parse($month . '-01')->startOfMonth(),
                \Carbon\Carbon::parse($month . '-01')->endOfMonth(),
            ])
            ->with(['patient', 'doctor', 'duration'])
            ->orderBy('appointment_time')
            ->get();

        return response()->streamDownload(function () use ($appointments, $payments, $employer, $month) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['employer', 'month', 'appointment_id', 'visit_date', 'patient', 'doctor', 'minutes', 'amount_ugx']);
            $total = 0;
            foreach ($appointments as $a) {
                $amount = $payments->amountFor($a);
                $total += $amount;
                fputcsv($out, [$employer->name, $month, $a->id, $a->appointment_time->toDateTimeString(), $a->patient?->name, $a->doctor?->name, $a->duration?->minutes, $amount]);
            }
            fputcsv($out, [$employer->name, $month, 'TOTAL', '', '', '', '', $total]);
            fclose($out);
        }, 'invoice-' . \Illuminate\Support\Str::slug($employer->name) . '-' . $month . '.csv', ['Content-Type' => 'text/csv']);
    }

    /** Spend already committed by this employer in the month of the given date. */
    public static function spentInMonth(Employer $employer, \Carbon\Carbon $date, AppointmentPayments $payments): float
    {
        return (float) $employer->appointments()
            ->whereIn('status', ['confirmed', 'awaiting_approval', 'completed'])
            ->whereBetween('appointment_time', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])
            ->with(['duration', 'doctor'])
            ->get()
            ->sum(fn (Appointment $a) => $payments->amountFor($a));
    }
}
