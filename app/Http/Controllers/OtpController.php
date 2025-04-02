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
        // Explicit validation using Validator facade
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

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addHours(24);

        // Store OTP
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
        try {
    \Log::info('Attempting to send OTP to: ' . $request->email);
    Mail::to($request->email)->send(new SchoolOtpMail($otp));
    \Log::info('OTP sent successfully');
    
    return response()->json([
        'success' => true,
        'message' => 'OTP sent successfully'
    ]);
} catch (\Exception $e) {
    \Log::error('OTP Email Error: ' . $e->getMessage());
    \Log::error($e->getTraceAsString()); // Log stack trace
    
    return response()->json([
        'success' => false,
        'message' => 'Failed to send OTP: ' . $e->getMessage()
    ], 500);
}
    }
}