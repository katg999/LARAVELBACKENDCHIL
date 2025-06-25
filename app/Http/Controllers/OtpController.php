<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SchoolOtpMail;
use App\Models\Otp;
use App\Models\School;
use App\Models\Doctor;
use App\Models\HealthFacility;

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
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    $isInSchools = \App\Models\School::where('email', $value)->exists();
                    $isInHealthFacilities = \App\Models\HealthFacility::where('email', $value)->exists();
        
                    if (!$isInSchools && !$isInHealthFacilities) {
                        $fail("The selected email is invalid.");
                    }
                }
            ]
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
        \Log::info('OTP Verification Request Received', [
            'email' => $request->email,
            'otp_received' => $request->otp,
            'time' => now()->toDateTimeString()
        ]);

        // Debug: Log all parameters received
        \Log::debug('Full Request Payload:', $request->all());

        $otpRecord = DB::table('otps')
              ->where('email', $request->email)
              ->first();

        if (!$otpRecord) {
            \Log::warning('No OTP record found for email', [
                'email' => $request->email
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No OTP record found for this email'
            ], 401);
        }

        \Log::debug('Database OTP Record:', (array)$otpRecord);

        $verification = DB::table('otps')
              ->where('email', $request->email)
              ->where('code', $request->otp)
              ->where('expires_at', '>', now())
              ->where('used', false)
              ->first();

        if (!$verification) {
            \Log::warning('OTP Verification Failed', [
                'possible_reasons' => [
                    'code_mismatch' => $request->otp != $otpRecord->code,
                    'expired' => $otpRecord->expires_at <= now(),
                    'already_used' => $otpRecord->used == true,
                    'types' => [
                        'received_otp_type' => gettype($request->otp),
                        'stored_otp_type' => gettype($otpRecord->code)
                    ],
                    'values' => [
                        'received' => $request->otp,
                        'stored' => $otpRecord->code
                    ]
                ]
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
                'debug' => [
                    'received_otp' => $request->otp,
                    'stored_otp' => $otpRecord->code,
                    'expired' => $otpRecord->expires_at <= now(),
                    'used' => $otpRecord->used
                ]
            ], 401);
        }

        // Mark as used
        DB::table('otps')
          ->where('id', $otpRecord->id)
          ->update(['used' => true]);

        // Get the school information
        $school = School::where('email', $request->email)->first();

        \Log::info('OTP Verified Successfully', [
            'email' => $request->email,
            'school_id' => $school ? $school->id : null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully. Access your dashboard below.',
            'dashboard_url' => 'https://laravelbackendchil.onrender.com/school-dashboard/'.$school->id,
            // Optional: Add these for Voiceflow debugging
            'school_id' => $school->id,
            'school_name' => $school->name
        ]);
    }
    public function verifyDoctorOtp(Request $request)
    {
        \Log::info('Doctor OTP Verification Request', [
            'email' => $request->email,
            'otp_received' => $request->otp,
            'time' => now()->toDateTimeString()
        ]);
    
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:doctors,email',
            'otp' => 'required|string|min:6|max:6'
        ]);
    
        if ($validator->fails()) {
            \Log::warning('Doctor OTP Validation Failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        try {
            // Check OTP record (using same structure as school verification)
            $otpRecord = DB::table('otps')
                ->where('email', $request->email)
                ->first();
    
            if (!$otpRecord) {
                \Log::warning('No OTP record found for doctor', [
                    'email' => $request->email
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No OTP record found'
                ], 401);
            }
    
            // Verify OTP
            $verification = DB::table('otps')
                ->where('email', $request->email)
                ->where('code', $request->otp)
                ->where('expires_at', '>', now())
                ->where('used', false)
                ->exists();
    
            if (!$verification) {
                \Log::warning('Doctor OTP Verification Failed', [
                    'email' => $request->email,
                    'reason' => $otpRecord->code != $request->otp ? 'code_mismatch' : 
                               ($otpRecord->expires_at <= now() ? 'expired' : 'already_used')
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP'
                ], 401);
            }
    
            // Mark as used
            DB::table('otps')
                ->where('email', $request->email)
                ->update(['used' => true]);
    
            // Get doctor details
            $doctor = Doctor::where('email', $request->email)->first();
    
            \Log::info('Doctor OTP Verified Successfully', [
                'doctor_id' => $doctor->id,
                'email' => $request->email
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully',
                'dashboard_url' => url("/doctor-dashboard/{$doctor->id}"),
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->name
            ]);
    
        } catch (\Exception $e) {
            \Log::error('Doctor OTP Verification Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Server error during verification'
            ], 500);
        }
    }

    public function verifyHealthFacilityOtp(Request $request)
{
    \Log::info('Health Facility OTP Verification Request', [
        'email' => $request->email,
        'otp_received' => $request->otp,
        'time' => now()->toDateTimeString()
    ]);

    // Validate input
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:health_facilities,email',
        'otp' => 'required|string|min:6|max:6'
    ]);

    if ($validator->fails()) {
        \Log::warning('Health Facility OTP Validation Failed', [
            'errors' => $validator->errors()->toArray()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        // Check OTP record
        $otpRecord = DB::table('otps')
            ->where('email', $request->email)
            ->first();

        if (!$otpRecord) {
            \Log::warning('No OTP record found for health facility', [
                'email' => $request->email
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No OTP record found'
            ], 401);
        }

        // Verify OTP
        $verification = DB::table('otps')
            ->where('email', $request->email)
            ->where('code', $request->otp)
            ->where('expires_at', '>', now())
            ->where('used', false)
            ->exists();

        if (!$verification) {
            \Log::warning('Health Facility OTP Verification Failed', [
                'email' => $request->email,
                'reason' => $otpRecord->code != $request->otp ? 'code_mismatch' : 
                           ($otpRecord->expires_at <= now() ? 'expired' : 'already_used')
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 401);
        }

        // Mark as used
        DB::table('otps')
            ->where('email', $request->email)
            ->update(['used' => true]);

        // Get health facility details
        $healthFacility = HealthFacility::where('email', $request->email)->first();

        \Log::info('Health Facility OTP Verified Successfully', [
            'health_facility_id' => $healthFacility->id,
            'email' => $request->email
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'dashboard_url' => url("/health-facility/dashboard/{$healthFacility->id}"),
            'health_facility_id' => $healthFacility->id,
            'health_facility_name' => $healthFacility->name
        ]);

    } catch (\Exception $e) {
        \Log::error('Health Facility OTP Verification Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Server error during verification'
        ], 500);
    }
}
}