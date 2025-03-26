<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use Illuminate\Support\Facades\Log;

class SchoolController extends Controller
{
    /**
     * Handle the registration of a school or updating the file URL.
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
            'file_url' => 'required|string', // Validate the file URL (required)
        ]);

        // Check if a school with the provided email already exists
        $school = School::where('email', $validated['email'])->first();

        if ($school) {
            // If the school exists, update the record with the file URL
            $school->update([
                'file_url' => $validated['file_url'],
            ]);

            // Log the successful update
            Log::info('School record updated with file URL:', ['school' => $school]);

            return response()->json([
                'message' => 'School record updated with file URL',
                'school' => $school
            ], 200);
        } else {
            // If the school does not exist, create a new school record
            $school = School::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'contact' => $validated['contact'],
                'file_url' => $validated['file_url'], // Store the file URL (required)
            ]);

            // Log the successful registration
            Log::info('School registered successfully:', ['school' => $school]);

            return response()->json([
                'message' => 'School registered successfully',
                'school' => $school
            ], 201);
        }
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