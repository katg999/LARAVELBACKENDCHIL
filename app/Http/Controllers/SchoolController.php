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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:schools',
            'contact' => 'required|string|max:20',
            'file_url' => 'nullable|string'
        ]);

        $school = School::create([
            'name' => $request->name,
            'email' => $request->email,
            'contact' => $request->contact,
            'file_url' => $request->file_url
        ]);

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