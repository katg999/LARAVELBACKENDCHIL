<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use Illuminate\Support\Facades\Log;

class SchoolController extends Controller
{
    /**
     * Register a new school
     */
    public function registerSchool(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:schools',
            'contact' => 'required|string|max:20',
            'file_url' => 'nullable|string'
        ]);

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

    
public function showDashboard($id)
{
    $school = School::findOrFail($id);

    $doctors = Doctor::all(); // Fetch all doctors

    // Optional: fetch related data like messages, stats, etc. if needed

    return view('school-dashboard', [
        'school' => $school,
        'doctors' => $doctors
    ]);
}
}