<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SchoolOTPMail;
use App\Mail\SchoolRejectionMail;
use App\Mail\SchoolQueryMail;
use Illuminate\Support\Facades\Log;

class SchoolActionController extends Controller
{
    public function handleSchoolAction(Request $request)
    {
        // Validate the request
        $request->validate([
            'school_id' => 'required|integer',
            'email' => 'required|email',
            'action' => 'required|in:accept,reject,query',
            'message' => 'nullable|string'
        ]);

        $schoolId = $request->input('school_id');
        $email = $request->input('email');
        $action = $request->input('action');
        $message = $request->input('message');

        try {
            switch ($action) {
                case 'accept':
                    return $this->handleAcceptAction($schoolId, $email);
                case 'reject':
                    return $this->handleRejectAction($schoolId, $email, $message);
                case 'query':
                    return $this->handleQueryAction($schoolId, $email, $message);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid action specified'
                    ], 400);
            }
        } catch (\Exception $e) {
            Log::error("School action failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request'
            ], 500);
        }
    }

    private function handleAcceptAction($schoolId, $email)
    {
        // Generate OTP (6-digit code)
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Here you would typically save the OTP to the database associated with the school
        // Example:
        // School::where('id', $schoolId)->update(['otp' => $otp, 'otp_expires_at' => now()->addHours(24)]);
        
        // Send OTP email
        Mail::to($email)->send(new SchoolOTPMail($otp));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully'
        ]);
    }

    private function handleRejectAction($schoolId, $email, $message)
    {
        // Default message if none provided
        $rejectionMessage = $message ?: 'Your document has been refused due to invalidity. Please register again with the right document.';
        
        // Here you might want to update the school status in your database
        // Example:
        // School::where('id', $schoolId)->update(['status' => 'rejected']);
        
        // Send rejection email
        Mail::to($email)->send(new SchoolRejectionMail($rejectionMessage));

        return response()->json([
            'success' => true,
            'message' => 'Rejection sent successfully'
        ]);
    }

    private function handleQueryAction($schoolId, $email, $message)
    {
        if (empty($message)) {
            return response()->json([
                'success' => false,
                'message' => 'Query message is required'
            ], 400);
        }
        
        // Here you might want to update the school status in your database
        // Example:
        // School::where('id', $schoolId)->update(['status' => 'needs_revision']);
        
        // Send query email
        Mail::to($email)->send(new SchoolQueryMail($message));

        return response()->json([
            'success' => true,
            'message' => 'Query sent successfully'
        ]);
    }
}