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


Route::post('/register-school', function (Request $request) {
    // Validate incoming data
    $validated = $request->validate([
        'name' => 'required|string',
        'email' => 'required|email|unique:schools,email',
        'contact' => 'required|string',
        // Remove the document validation line
    ]);
    
    // Create a new school record
    $school = School::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'contact' => $validated['contact'],
        // Remove the document field
    ]);
    
    return response()->json([
        'message' => 'School registered successfully',
        'school' => $school
    ], 201);
});