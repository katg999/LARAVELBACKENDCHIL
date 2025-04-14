<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'student_id' => 'required|exists:students,id',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_time' => 'required|date',
            'reason' => 'required|string'
        ]);

        $appointment = Appointment::create($validated);

        return response()->json([
            'success' => true,
            'appointment' => $appointment
        ]);
    }

    public function index(School $school)
    {
        return $school->appointments()
            ->with(['student', 'doctor'])
            ->latest()
            ->get();
    }
}