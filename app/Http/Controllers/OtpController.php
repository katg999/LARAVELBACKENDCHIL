<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; // MUST be Illuminate\Http\Request
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SchoolOtpMail;

class OtpController extends Controller
{
   public function sendOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'school_id' => 'required|exists:schools,id',
        'email' => 'required|email'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = now()->addHours(24);

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

    try {
    \Log::info('Attempting to send OTP', [
        'email' => $request->email,
        'school_id' => $request->school_id,
        'mail_config' => config('mail')
    ]);
    
    Mail::to($request->email)->send(new SchoolOtpMail($otp));
    
    if (count(Mail::failures())) {
        throw new \Exception('Mail delivery failed');
    }
    
    \Log::info('OTP sent successfully');
    return response()->json(['success' => true]);
    
} catch (\Exception $e) {
    \Log::error('OTP Send Failure', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'request' => $request->all(),
        'config' => config('mail')
    ]);
    
    return response()->json([
        'success' => false,
        'message' => 'Failed to send OTP. Please try again later.'
    ], 500);
}
}
}