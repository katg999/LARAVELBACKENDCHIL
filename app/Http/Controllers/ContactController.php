<?php

namespace App\Http\Controllers;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string',
            'accept_policy' => 'required|boolean'
        ]);

        $submission = ContactSubmission::create($validated);

        return response()->json([
            'message' => 'Contact form submitted successfully',
            'data' => $submission
        ], 201);
    }

    public function index()
    {
        $submissions = ContactSubmission::latest()->get();
        return view('contact-us', ['submissions' => $submissions]);
    }
}