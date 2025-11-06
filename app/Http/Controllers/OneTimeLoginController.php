<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use App\Models\OneTimeLoginToken;

class OneTimeLoginController extends Controller
{
    public function consume($token, Request $request)
    {
        // Find token record
        $record = OneTimeLoginToken::where('token', $token)->first();

        if (!$record) {
            return response()->view('one-time-login.error', [
                'message' => 'Invalid or expired login link.',
                'seconds' => 10
            ]);
        }

        if ($record->used) {
            return response()->view('one-time-login.error', [
                'message' => 'This login link has already been used.',
                'seconds' => 10
            ]);
        }

        if ($record->isExpired()) {
            return response()->view('one-time-login.error', [
                'message' => 'This login link has expired.',
                'seconds' => 10
            ]);
        }

        // Get the user based on type
        $user = null;
        $redirectUrl = '';
        $entityUser = null; // The actual entity (School, Doctor, HealthFacility) or User

        switch ($record->user_type) {
            case 'school':
                $entityUser = \App\User::find($record->user_id);
                $redirectUrl = url("/school-dashboard");
                break;
            case 'doctor':
                $entityUser = \App\Models\Doctor::find($record->user_id);
                $redirectUrl = url('/doctor/dashboard');
                break;
            case 'health_facility':
                $entityUser = \App\User::find($record->user_id);
                $redirectUrl = url("/health-facility/dashboard");
                break;
            default:
                return response()->view('one-time-login.error', [
                    'message' => 'Invalid user type.',
                    'seconds' => 10
                ]);
        }

        if (!$entityUser) {
            return response()->view('one-time-login.error', [
                'message' => 'User account not found.',
                'seconds' => 10
            ]);
        }

        // Handle authentication based on user type
        switch ($record->user_type) {
            case 'school':
                return $this->handleSchoolUserLogin($entityUser, $record);
            case 'doctor':
                return $this->handleDoctorLogin($entityUser, $record);
            case 'health_facility':
                return $this->handleHealthFacilityUserLogin($entityUser, $record);
            default:
                return response()->view('one-time-login.error', [
                    'message' => 'Invalid user type.',
                    'seconds' => 10
                ]);
        }
    }

    protected function handleSchoolUserLogin($user, $record)
    {
        // Authenticate the existing user
        Auth::login($user);

        // Mark token as used
        $record->markAsUsed();

        \Log::info('School user login successful', [
            'user_id' => $user->id,
            'school_id' => $user->school_id,
            'email' => $record->email
        ]);

        return redirect($record->redirectUrl ?? url('/school-dashboard'));
    }

    protected function handleDoctorLogin($doctor, $record)
    {
        // Flush other sessions for this doctor (best-effort)
        // Note: flushSessionsForDoctor method not implemented yet
        // $this->flushSessionsForDoctor($doctor);

        // Ensure any currently authenticated user is logged out and session invalidated
        try {
            $guard = Auth::guard();
            $guard->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            // Forget remember-me recaller cookie if present
            $recaller = $guard->getRecallerName();
            if ($recaller) {
                Cookie::queue(Cookie::forget($recaller));
            }
        } catch (\Exception $e) {
            // ignore
        }

        // Store doctor info in session for session-based auth
        request()->session()->put('authenticated_user', [
            'type' => $record->user_type,
            'id' => $doctor->id,
            'name' => $doctor->name,
            'email' => $record->email
        ]);

        // Set session lifetime to 12 hours (720 minutes) for one-time login users
        request()->session()->put('_session_lifetime', 720);

        // Mark token as used
        $record->markAsUsed();

        return redirect($record->redirectUrl ?? url('/doctor/dashboard'));
    }

    protected function handleHealthFacilityUserLogin($user, $record)
    {
        // Authenticate the existing user
        Auth::login($user);

        // Mark token as used
        $record->markAsUsed();

        \Log::info('Health facility user login successful', [
            'user_id' => $user->id,
            'health_facility_id' => $user->health_facility_id,
            'email' => $record->email
        ]);

        return redirect($record->redirectUrl ?? url('/health-facility/dashboard'));
    }

    // Note: Only school and health facility admins/staff can login via VoiceFlow OTP
    // They use their personal credentials (personal emails) instead of entity emails
    // VoiceFlow OTP validates that the personal email belongs to an authorized user
}
