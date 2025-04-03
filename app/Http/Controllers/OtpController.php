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


        \Log::info('Attempting to send OTP to: ' . $request->email);
        
        // Send immediately (bypass queue for testing)
        Mail::to($request->email)->send(new SchoolOtpMail($otp));
        
        // Verify no failures
        if (count(Mail::failures()) > 0) {
            throw new \Exception('Failed to deliver to recipient');
        }
        
        \Log::info('OTP sent successfully');
        return response()->json(['success' => true, 'message' => 'OTP sent']);
        
    } catch (\Exception $e) {
        \Log::error('OTP Email Error', [
            'message' => $e->getMessage(),
            'exception' => $e,
            'trace' => $e->getTraceAsString(),
            'mail_config' => config('mail') // Log current mail config
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to send OTP. Please try again later.'
        ], 500);
    }

}
}