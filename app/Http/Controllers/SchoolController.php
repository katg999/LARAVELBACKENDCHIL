<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SchoolController extends Controller
{
    /**
     * Register a new school
     */
    public function registerSchool(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:schools',
                'contact' => 'required|string|max:20',
                'file_url' => 'nullable'
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'message' => 'Error validating school data'
            ], 422);
        }

        $school = School::create($validated);

        return response()->json([
            'message' => 'School registered successfully',
            'school' => $school
        ], 201);
    }

    /**
     * Get all schools
     */
    public function getSchools()
    {
        return response()->json(School::all());
    }

    /**
     * Update file URL for most recently created school
     * (New dedicated endpoint for your Voiceflow integration)
     */
    public function updateLatestSchoolFile(Request $request)
    {
        $school = School::latest()->first();

        if (!$school) {
            return response()->json([
                'message' => 'No school records found to update'
            ], 404);
        }

        $validated = $request->validate([
            'file_url' => 'required|string|url' // Ensures valid URL format
        ]);

        $school->update(['file_url' => $validated['file_url']]);

        Log::info('Latest school file URL updated', [
            'school_id' => $school->id,
            'file_url' => $validated['file_url']
        ]);

        return response()->json([
            'message' => 'File URL updated for most recent school',
            'school_id' => $school->id,
            'file_url' => $school->file_url
        ]);
    }

    /**
     * Update specific school by ID (original functionality)
     */
    public function updateSchool(Request $request, $id)
    {
        $school = School::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:schools,email,'.$school->id,
            'contact' => 'sometimes|string|max:20',
            'file_url' => 'nullable|string'
        ]);

        $school->update($validated);

        return response()->json([
            'message' => 'School updated successfully',
            'school' => $school
        ]);
    }

    public function showDashboard(Request $request)
    {
        $user = Auth::user();
        
        // Get the school associated with the authenticated user
        $school = School::findOrFail($user->school_id);

        $students = $school->students()->latest()->get();
        $labTests = $school->labTests()->with('patient')->latest()->get();
        $appointments = $school->appointments()->with(['patient', 'doctor'])->latest()->get();
        $doctors = $school->doctors()->latest()->get();

        // Calculate weekly data for the chart (last 7 days)
        $weeklyAppointments = [];
        $weeklyLabTests = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            
            $appointmentCount = $school->appointments()
                ->whereDate('created_at', $date)
                ->count();
                
            $labTestCount = $school->labTests()
                ->whereDate('created_at', $date)
                ->count();
                
            $weeklyAppointments[] = $appointmentCount;
            $weeklyLabTests[] = $labTestCount;
        }

        return view('school.school-dashboard', [
            'school' => $school,
            'students' => $students,
            'labTests' => $labTests,
            'appointments' => $appointments,
            'doctors' => $doctors,
            'studentsCount' => $students->count(),
            'appointmentsCount' => $appointments->count(),
            'labTestsCount' => $labTests->count(),
            'doctorsCount' => $doctors->count(),
            'weeklyAppointments' => $weeklyAppointments,
            'weeklyLabTests' => $weeklyLabTests,
        ]);
    }
}