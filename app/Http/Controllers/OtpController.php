<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SchoolOtpMail;
use App\Models\Otp;

class OtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|exists:schools,id',
            'email' => 'required|email'
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate a 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addHours(24); // OTP expiration time

        // Store or update OTP in the database
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
            // Log the email sending attempt (useful for debugging)
            \Log::info('Attempting to send OTP to: ' . $request->email);
            
            // Send OTP email immediately (bypass queue for testing)
            Mail::to($request->email)->send(new SchoolOtpMail($otp));
            
            // Verify no failures occurred
            if (count(Mail::failures()) > 0) {
                throw new \Exception('Failed to deliver to recipient');
            }
            
            // Log success and return response
            \Log::info('OTP sent successfully');
            return response()->json(['success' => true, 'message' => 'OTP sent']);
        } catch (\Exception $e) {
            // Log the exception in case of failure
            \Log::error('OTP Email Error', [
                'message' => $e->getMessage(),
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'mail_config' => config('mail') // Log current mail config for debugging
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again later.'
            ], 500);
        }
    }
}