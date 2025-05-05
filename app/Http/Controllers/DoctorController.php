<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Doctor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DoctorController extends Controller
{
    /**
     * Display doctors dashboard
     */
    public function dashboard()
    {
        try {
            // Try fetching from API first
            $response = Http::get('https://laravelbackendchil.onrender.com/api/doctors');
            
            if ($response->successful()) {
                $doctors = $response->json();
                $error = null;
            } else {
                // Fallback to local database if API fails
                $doctors = Doctor::all()->toArray();
                $error = 'Using local data: ' . $response->status();
            }
            
        } catch (\Exception $e) {
            // Final fallback to empty data with error message
            $doctors = [];
            $error = 'Error: ' . $e->getMessage();
        }

        return view('api-dashboard-doctors', [
            'doctors' => $doctors,
            'error' => $error
        ]);
    }

    /**
     * Register a new doctor
     */
    public function registerDoctor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:doctors',
            'contact' => 'required|string|max:20',
            'specialization' => 'nullable|string|max:255',
            'file_url' => 'nullable|string'
        ]);

        $doctor = Doctor::create($validated);

        return response()->json([
            'message' => 'Doctor registered successfully',
            'doctor' => $doctor
        ], 201);
    }

    /**
     * Get all doctors (API endpoint)
     */
    public function getDoctors()
    {
        return response()->json(Doctor::all());
    }

    /**
     * Update file URL for most recently created doctor
     */
    public function updateLatestDoctorFile(Request $request)
{
    $doctor = Doctor::latest()->first();

    if (!$doctor) {
        return response()->json([
            'message' => 'No doctor records found to update'
        ], 404);
    }

    $validated = $request->validate([
        'file_url' => 'required|string'
    ]);

    $doctor->file_url = $validated['file_url'];
    $doctor->save();

    return response()->json([
        'message' => 'File URL updated for most recent doctor',
        'doctor_id' => $doctor->id,
        'file_url' => $doctor->file_url
    ]);
}


    /**
     * Update specific doctor by ID
     */
    public function updateDoctor(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:doctors,email,'.$doctor->id,
            'contact' => 'sometimes|string|max:20',
            'specialization' => 'nullable|string|max:255',
            'file_url' => 'nullable|string'
        ]);

        $doctor->update($validated);

        return response()->json([
            'message' => 'Doctor updated successfully',
            'doctor' => $doctor
        ]);
    }
}