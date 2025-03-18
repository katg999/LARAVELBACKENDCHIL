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
        // Validate the incoming data
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'contact' => 'required|string',
            'document' => 'required',  // 'document' is required but can be a file or a URL
        ]);

        // Save the school data
        $school = new School();
        $school->name = $validated['name'];
        $school->email = $validated['email'];
        $school->contact = $validated['contact'];

        // Check if the document is a file or a URL
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
            return response()->json(['message' => 'Invalid document format'], 400);
        }

        // Save the school data
        $school->save();

        return response()->json(['message' => 'School registered successfully', 'school' => $school]);
    }
}
