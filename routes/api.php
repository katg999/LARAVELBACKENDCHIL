<?php

use Illuminate\Http\Request;
use App\Models\School; // Add this import
use App\Http\Controllers\SchoolController; 
use App\Http\Controllers\FinanceLoanController; // Add this import
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\OtpController;


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


    // Contact form routes
Route::post('/contact-submissions', [ContactController::class, 'store']);
Route::get('/contact-submissions', [ContactController::class, 'index']); // Optional: if you need to fetch submissions

Route::post('/send-otp', [OtpController::class, 'sendOtp']);

// For VoiceFlow login OTP
Route::post('/voiceflow/send-login-otp', [OtpController::class, 'sendLoginOtp']);
Route::post('/voiceflow/verify-otp', [OtpController::class, 'verifyOtp']);


// routes/api.php
Route::post('/students', function(Request $request) {
    try {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:255',
            'grade' => 'required|string',
            'birth_date' => 'required|date',
            'parent_contact' => 'nullable|string'
        ]);

        $student = App\Models\Student::create($validated);

        return response()->json([
            'success' => true,
            'student' => $student
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
})->name('api.students.store');




// Appointments
Route::post('/appointments', function(Request $request) {
    $validated = $request->validate([
        'school_id' => 'required|exists:schools,id',
        'student_id' => 'required|exists:students,id',
        'doctor_id' => 'required|exists:doctors,id',
        'appointment_time' => 'required|date',
        'reason' => 'required|string'
    ]);

    $appointment = App\Models\Appointment::create($validated);

    return response()->json([
        'success' => true,
        'appointment' => $appointment
    ]);
});

// Lab Tests
Route::post('/lab-tests', function(Request $request) {
    $validated = $request->validate([
        'school_id' => 'required|exists:schools,id',
        'student_id' => 'required|exists:students,id',
        'test_type' => 'required|string',
        'notes' => 'nullable|string'
    ]);

    $labTest = App\Models\LabTest::create(array_merge($validated, [
        'status' => 'pending'
    ]));

    return response()->json([
        'success' => true,
        'lab_test' => $labTest
    ]);
});