<?php

namespace App\Http\Controllers;
use App\Models\Doctor;
use Illuminate\Http\Request;


class DoctorAvailabilityController extends Controller
{
    public function update(Request $request, Doctor $doctor)
    {
        foreach ($request->input('days', []) as $day => $data) {
            $doctor->availabilities()->updateOrCreate(
                ['day' => strtolower($day)],
                [
                    'available' => isset($data['available']),
                    'max_appointments' => $data['max_appointments'] ?? 0,
                ]
            );
        }

        return redirect()->back()->with('success', 'Availability updated successfully.');
    }
}