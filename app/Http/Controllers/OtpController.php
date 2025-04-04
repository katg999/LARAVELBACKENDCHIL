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
            \Log::warning('OTP Validation Failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generate a 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addHours(24); // OTP expiration time

            // Log before DB operation
            \Log::info('Storing OTP in database', [
                'school_id' => $request->school_id,
                'expires_at' => $expiresAt
            ]);

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
            
            // Log email configuration details
            \Log::info('Email configuration', [
                'mail_driver' => config('mail.default'),
                'mail_host' => config('mail.host'),
                'mail_port' => config('mail.port'),
                'mail_from_address' => config('mail.from.address'),
                'mail_from_name' => config('mail.from.name'),
                'mail_encryption' => config('mail.encryption'),
                'template_exists' => view()->exists('emails.otp')
            ]);

            // Log the email sending attempt
            \Log::info('Attempting to send OTP to: ' . $request->email, [
                'to_email' => $request->email,
                'otp_length' => strlen($otp)
            ]);
            
            // Send OTP email immediately (bypass queue for testing)
            Mail::to($request->email)->send(new SchoolOtpMail($otp));
            
            // Verify no failures occurred
            if (count(Mail::failures()) > 0) {
                \Log::error('Mail delivery failed', [
                    'failures' => Mail::failures()
                ]);
                throw new \Exception('Failed to deliver to recipient');
            }
            
            // Log success and return response
            \Log::info('OTP sent successfully');
            return response()->json(['success' => true, 'message' => 'OTP sent']);
            
        } catch (\Exception $e) {
            // Log the exception in case of failure with maximum details
            \Log::error('OTP Email Error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'mail_config' => [
                    'driver' => config('mail.default'),
                    'host' => config('mail.host'),
                    'port' => config('mail.port'),
                    'from_address' => config('mail.from.address'),
                    'encryption' => config('mail.encryption')
                ],
                'request_data' => $request->except(['_token'])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again later.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    // Add a test endpoint for checking mail configuration
    public function testMailConfig()
    {
        return response()->json([
            'mail_config' => [
                'driver' => config('mail.default'),
                'host' => config('mail.host'),
                'port' => config('mail.port'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'encryption' => config('mail.encryption')
            ],
            'template_exists' => view()->exists('emails.otp')
        ]);
    }




/**
 * Handle OTP request from VoiceFlow (email only)
 */
public function sendLoginOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:schools,email'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid email address'
        ], 422);
    }

    try {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addHours(24);

        // Store OTP with email (no school_id)
        DB::table('otps')->updateOrInsert(
            ['email' => $request->email],
            [
                'code' => $otp,
                'expires_at' => $expiresAt,
                'used' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        Mail::to($request->email)->send(new SchoolOtpMail($otp));
        
        return response()->json(['success' => true]);

    } catch (\Exception $e) {
        \Log::error('OTP Error: '.$e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to send OTP'
        ], 500);
    }
}

/**
 * Verify OTP from VoiceFlow
 */
public function verifyOtp(Request $request)
{
    $otp = DB::table('otps')
          ->where('email', $request->email)
          ->where('code', $request->otp)
          ->where('expires_at', '>', now())
          ->where('used', false)
          ->first();

    if (!$otp) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired OTP'
        ], 401);
    }

    // Mark as used
    DB::table('otps')
      ->where('id', $otp->id)
      ->update(['used' => true]);

    return response()->json([
        'success' => true,
        'message' => 'OTP verified'
    ]);
}

}