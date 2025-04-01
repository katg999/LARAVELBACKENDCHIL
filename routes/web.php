<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FinanceDashboardController;
use App\Http\Controllers\ApiDashboardController;
use App\Models\NewsletterSubscriber;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\OtpController;

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


Route::post('/send-otp', function(Request $request) {
    try {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'email' => 'required|email'
        ]);

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addHours(24);

        // Store OTP in database
        DB::table('otps')->updateOrInsert(
            ['school_id' => $request->school_id],
            [
                'code' => $otp,
                'expires_at' => $expiresAt,
                'used' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Send email
        Mail::send('emails.otp', ['otp' => $otp], function($message) use ($request) {
            $message->to($request->email)
                    ->subject('Your School Login OTP');
        });

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to send OTP: ' . $e->getMessage()
        ], 500);
    }
});