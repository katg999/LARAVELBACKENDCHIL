<?php

use Illuminate\Http\Request;
use App\Models\School; // Add this import
use App\Http\Controllers\SchoolController; 
use App\Http\Controllers\FinanceLoanController; // Add this import

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

// Use the SchoolController method for /register-school
Route::post('/register-school', [SchoolController::class, 'registerSchool']);

// New GET route for fetching schools
Route::get('/schools', [SchoolController::class, 'getSchools']);

// New endpoint for Finance Loan Data
Route::post('/finance-loan-data', [FinanceLoanController::class, 'storeFinanceLoanData']);




