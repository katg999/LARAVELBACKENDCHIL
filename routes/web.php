<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\FinanceDashboardController;
use App\Http\Controllers\ApiDashboardController;
use App\Models\NewsletterSubscriber;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\OtpController;
use Illuminate\Http\Request; 

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| which contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Home Route (Fixed Controller Reference)
Route::get('/home', [HomeController::class, 'index'])->name('home');

// API Dashboard Route
Route::get('/api-dashboard', [ApiDashboardController::class, 'index'])->name('api-dashboard');

// Finance Dashboard Route
Route::get('/finance-dashboard', [FinanceDashboardController::class, 'index'])->name('finance-dashboard');


// If you need a web view for admin purposes
//Route::get('/admin/contact-submissions', function () {
  //  return view('contact-submissions');
//});


Route::get('/admin/contact-submissions', [ContactController::class, 'index'])
     ->name('admin.contact-submissions');


// ✅ Email Verification Route for Newsletter
Route::get('/verify-newsletter/{token}', function ($token) {
    $subscriber = NewsletterSubscriber::where('verification_token', $token)->first();

    if (!$subscriber) {
        return response()->json(['message' => 'Invalid or expired verification link.'], 404);
    }

    // Mark as verified
    $subscriber->update(['is_verified' => true]);

    return response()->json(['message' => 'Email confirmed!']);
})->name('verify-newsletter');

Route::post('/send-otp', [App\Http\Controllers\OtpController::class, 'sendOtp']);

// routes/web.php
// Route::get('/school-dashboard/{school}', function (App\Models\School $school) {
//     return view('school-dashboard', [
//         'school' => $school,
//         'students' => $school->students()->with(['appointments', 'labTests'])->latest()->get(),
//         'appointments' => $school->appointments()->with(['student', 'doctor'])->latest()->get(),
//         'labTests' => $school->labTests()->with('student')->latest()->get(),
//         'doctors' => $school->doctors()->latest()->get()
//     ]);
// })->name('school.dashboard');




Route::get('/school-dashboard/{school}', function (App\Models\School $school) {
    return view('school-dashboard', [
        'school' => $school,
        'students' => $school->students()->latest()->get(), // Removed with()
        'appointments' => $school->appointments()->with(['student', 'doctor'])->latest()->get(),
        'labTests' => $school->labTests()->with('student')->latest()->get(),
        'doctors' => $school->doctors()->latest()->get()
    ]);
})->name('school.dashboard');


// Doctor Dashboard Route
Route::get('/doctors-dashboard', [DoctorController::class, 'dashboard']);


Route::middleware(['auth:doctor'])->group(function () {
    Route::get('/doctor/dashboard', [DoctorController::class, 'authDashboard'])->name('doctor.dashboard');
    // Add routes for other methods if not already defined
});


Route::get('/doctor-dashboard', function () {
    return view('doctor-dashboard'); // points to resources/views/doctor-dashboard.blade.php
});


Route::get('/doctor-dashboard/{doctorId}', [DoctorController::class, 'showDoctorDashboard'])->name('doctor.dashboard');
