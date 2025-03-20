<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use Illuminate\Support\Facades\Log; 

class SchoolController extends Controller
{
    /**
     * Handle the registration of a school.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function registerSchool(Request $request)
    {
        // Log the incoming request data for debugging
        Log::info('Incoming request data:', $request->all());

        // Validate the incoming data
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:schools,email', // Ensure the email is unique
            'contact' => 'required|string',
            'file_url' => 'nullable|url', // Validate the file URL (optional)
        ]);

        // Save the school data
        $school = School::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'contact' => $validated['contact'],
            'file_url' => $validated['file_url'] ?? null, // Store the file URL if provided
        ]);

        // Log the successful registration
        Log::info('School registered successfully:', ['school' => $school]);

        return response()->json([
            'message' => 'School registered successfully',
            'school' => $school
        ], 201);
    }

    /**
     * Fetch all schools.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSchools()
    {
        // Fetch all schools from the database
        $schools = School::all();

        return response()->json($schools);
    }
}