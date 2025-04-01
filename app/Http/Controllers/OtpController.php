<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SchoolOtpMail;

class OtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'email' => 'required|email'
        ]);

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
            Mail::to($request->email)->send(new SchoolOtpMail($otp));
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
    }
}

   