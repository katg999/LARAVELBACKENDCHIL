<?php

use Illuminate\Http\Request;
use App\Models\School; // Add this import
use App\Http\Controllers\SchoolController; 
use App\Http\Controllers\FinanceLoanController; // Add this import
use App\Http\Controllers\NewsletterController;

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

Route::patch('/update-latest-school-file', [SchoolController::class, 'updateLatestSchoolFile']);


// New endpoint for Finance Loan Data
Route::post('/finance-loan-data', [FinanceLoanController::class, 'storeFinanceLoanData']);

// New endpoint for Finance Loan Data
Route::post('/finance-loan-data', [FinanceLoanController::class, 'storeFinanceLoanData']);

// New GET route for fetching finance loan data
Route::get('/finance-loan-data', [FinanceLoanController::class, 'getFinanceLoanData']);

//Subscribe Newsletter Functionality
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:3,1'); // 5 requests per minute

Route::get('/verify-newsletter/{token}', [NewsletterController::class, 'verify'])
    ->name('newsletter.verify');




