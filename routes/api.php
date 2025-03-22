<?php

use Illuminate\Http\Request;
use App\Http\Controllers\SchoolController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Example route for testing
Route::get('/hello', function () {
    return 'Hello, Laravel!';
});

// Register a school (with CORS support)
Route::post('/register-school', [SchoolController::class, 'registerSchool']);

// Fetch all schools
Route::get('/schools', [SchoolController::class, 'getSchools']);

// Optional: Handle OPTIONS requests for CORS preflight
Route::options('/register-school', function () {
    return response()->json();
});
