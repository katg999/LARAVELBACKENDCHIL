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
            'document' => 'nullable',  // 'document' is now optional
        ]);

        // Save the school data
        $school = new School();
        $school->name = $validated['name'];
        $school->email = $validated['email'];
        $school->contact = $validated['contact'];

        // Check if the document is provided and is a file or a URL
        if ($request->has('document')) {
            if ($request->hasFile('document')) {
                // If it's a file upload, store it
                $file = $request->file('document');
                $path = $file->store('school_documents', 'public'); // Store the document in the public directory
                $school->document_path = $path;
            } elseif (filter_var($validated['document'], FILTER_VALIDATE_URL)) {
                // If it's a URL, store the URL directly
                $school->document_path = $validated['document'];
            } else {
                // If the document is neither a file nor a valid URL, return an error
                Log::error('Invalid document format:', ['document' => $validated['document']]);
                return response()->json(['message' => 'Invalid document format'], 400);
            }
        } else {
            // If the document is not provided, set it to null
            $school->document_path = null;
        }

        // Save the school data
        $school->save();

        // Log the successful registration
        Log::info('School registered successfully:', ['school' => $school]);

        return response()->json(['message' => 'School registered successfully', 'school' => $school]);
    }
}