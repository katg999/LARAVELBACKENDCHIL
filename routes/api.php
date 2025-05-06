<?php

use Illuminate\Http\Request;
use App\Models\School; // Add this import
use App\Http\Controllers\SchoolController; 
use App\Http\Controllers\FinanceLoanController; // Add this import
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SchoolActionController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DoctorAvailabilityController;



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
        'duration' => 'required|in:15,20',
        'appointment_time' => 'required|date',
        'reason' => 'required|string|max:500'
    ]);

    // Calculate amount
    $doctor = Doctor::find($validated['doctor_id']);
    $isSpecialist = $doctor->specialization !== 'General Practitioner';
    $amount = $isSpecialist 
        ? ($validated['duration'] == 15 ? 100000 : 150000)
        : ($validated['duration'] == 15 ? 30000 : 45000);

    // Create appointment
    $appointment = Appointment::create([
        'school_id' => $validated['school_id'],
        'student_id' => $validated['student_id'],
        'doctor_id' => $validated['doctor_id'],
        'appointment_time' => $validated['appointment_time'],
        'duration' => $validated['duration'],
        'reason' => $validated['reason'],
        'amount' => $amount,
        'status' => 'pending_payment'
    ]);

    return response()->json([
        'success' => true,
        'appointment' => $appointment,
        'amount_due' => $amount
    ]);
})->name('api.appointments.store');

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



// Payment Routes
Route::prefix('momo')->group(function () {
    Route::post('/init', [PaymentController::class, 'initApiUser']); // One-time setup
    Route::get('/user', [PaymentController::class, 'getApiUser']);
    
    Route::post('/request-payment', [PaymentController::class, 'requestPayment']);
    Route::get('/payment-status/{referenceId}', [PaymentController::class, 'paymentStatus']);
    Route::get('/balance', [PaymentController::class, 'accountBalance']);
    
    Route::post('/callback', [PaymentController::class, 'handleCallback']);
});



// School Actions Routes
Route::post('/voiceflow/school-action', [SchoolActionController::class, 'handleSchoolAction']);


// Doctor routes
Route::post('/register-doctor', [DoctorController::class, 'registerDoctor']);
Route::get('/doctors', [DoctorController::class, 'getDoctors']);
Route::patch('/update-latest-doctor-file', [DoctorController::class, 'updateLatestDoctorFile']);
Route::patch('/doctors/{id}', [DoctorController::class, 'updateDoctor']);


Route::post('/doctors/{id}/update-meeting-link', [DoctorController::class, 'updateMeetingLink'])
    ->name('api.doctors.update-meeting-link');

Route::post('/doctors/{id}/update-availability', [DoctorController::class, 'updateAvailability'])
     ->name('api.doctors.update-availability');

Route::post('/doctors/{doctor}/availability', [DoctorAvailabilityController::class, 'update'])
    ->name('doctors.update-availability');


Route::post('/doctors/{doctor}/update', [DoctorController::class, 'update'])->name('api.doctors.update');
Route::post('/doctors/{doctor}/change-password', [DoctorController::class, 'changePassword'])->name('api.doctors.change-password');
Route::post('/doctors/{doctor}/update-notifications', [DoctorController::class, 'updateNotifications'])->name('api.doctors.update-notifications');
Route::post('/doctors/{doctor}/update-payment', [DoctorController::class, 'updatePayment'])->name('api.doctors.update-payment');
Route::post('/doctors/{doctor}/upload-image', [DoctorController::class, 'uploadImage'])->name('api.doctors.upload-image');
Route::post('/doctors/{doctor}/send-link', [DoctorController::class, 'sendLink'])->name('api.doctors.send-link');
Route::post('/doctors/{doctor}/update-online-status', [DoctorController::class, 'updateOnlineStatus'])->name('api.doctors.update-online-status');