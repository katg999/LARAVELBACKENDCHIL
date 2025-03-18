<?php

use Illuminate\Http\Request;
use App\Models\School; // Add this import

/*
|----------------------------------------------------------------------
| API Routes
|----------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/hello', function () {
    return 'Hello, Laravel!';
});

// Register school route with POST method
Route::post('/register-school', function (Request $request) {
    // Validate incoming data
    $validated = $request->validate([
        'name' => 'required|string',
        'email' => 'required|email|unique:schools,email',  // Ensure the email is unique
        'contact' => 'required|string',
        'document' => 'required|file|max:2048', // Ensure the file size is max 2MB
    ]);

    // Store the uploaded document in the "documents" directory inside storage/app/public
    $path = $request->file('document')->store('documents', 'public');

    // Create a new school record
    $school = School::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'contact' => $validated['contact'],
        'document' => $path,
    ]);

    return response()->json([
        'message' => 'School registered successfully',
        'school' => $school
    ], 201);
});
