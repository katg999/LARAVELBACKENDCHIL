<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;

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

        ]);

        // Save the school data
        $school = new School();
        $school->name = $validated['name'];
        $school->email = $validated['email'];
        $school->contact = $validated['contact'];


        // Save the school data
        $school->save();

        // Log the successful registration
        Log::info('School registered successfully:', ['school' => $school]);

        return response()->json(['message' => 'School registered successfully', 'school' => $school]);
    }
}