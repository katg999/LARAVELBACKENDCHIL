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
        $entityUser = null; // The actual entity (School, Doctor, HealthFacility)

        switch ($record->user_type) {
            case 'school':
                $entityUser = \App\Models\School::find($record->user_id);
                $redirectUrl = url("/school-dashboard");
                break;
            case 'doctor':
                $entityUser = \App\Models\Doctor::find($record->user_id);
                $redirectUrl = url('/doctor/dashboard');
                break;
            case 'health_facility':
                $entityUser = \App\Models\HealthFacility::find($record->user_id);
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

        // Health facilities and schools should NOT login with their entity email
        // Staff members use their personal emails through the invitation system
        // VoiceFlow OTP only validates entity ownership, not for dashboard access
        if ($record->user_type === 'health_facility' || $record->user_type === 'school') {
            return response()->view('one-time-login.error', [
                'message' => 'Please use staff invitation links to access the dashboard with your personal email. Entity emails are only for verification.',
                'seconds' => 15
            ]);
        }

        // Handle authentication based on user type
        // Only doctors can use VoiceFlow OTP for direct dashboard access
        if ($record->user_type === 'doctor') {
            // Flush other sessions for this doctor (best-effort)
            $this->flushSessionsForDoctor($entityUser);

            // Ensure any currently authenticated user is logged out and session invalidated
            try {
                $guard = Auth::guard();
                $guard->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Forget remember-me recaller cookie if present
                $recaller = $guard->getRecallerName();
                if ($recaller) {
                    Cookie::queue(Cookie::forget($recaller));
                }
            } catch (\Exception $e) {
                // ignore
            }

            // Store doctor info in session for session-based auth
            $request->session()->put('authenticated_user', [
                'type' => $record->user_type,
                'id' => $entityUser->id,
                'name' => $entityUser->name,
                'email' => $record->email
            ]);

            // Set session lifetime to 12 hours (720 minutes) for one-time login users
            $request->session()->put('_session_lifetime', 720);
        } else {
            // This shouldn't be reached as health_facility/school are blocked earlier
            return response()->view('one-time-login.error', [
                'message' => 'Invalid authentication type.',
                'seconds' => 10
            ]);
        }

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        // Mark token as used
        $record->markAsUsed();

        \Log::info('One-time login successful', [
            'user_type' => $record->user_type,
            'user_id' => $record->user_id,
            'email' => $record->email
        ]);

        // Redirect to appropriate dashboard
        return redirect($redirectUrl);
    }

    protected function flushSessionsForDoctor($doctor)
    {
        $driver = Config::get('session.driver', 'file');

        try {
            if ($driver === 'database') {
                // Remove rows in sessions table where user_id matches
                DB::table(Config::get('session.table', 'sessions'))
                    ->where('user_id', $doctor->id)
                    ->delete();

                // Also try a payload search as a fallback
                DB::table(Config::get('session.table', 'sessions'))
                    ->where('payload', 'like', '%' . $doctor->id . '%')
                    ->delete();
                return;
            }

            if ($driver === 'file') {
                $dir = storage_path('framework/sessions');
                if (File::isDirectory($dir)) {
                    $files = File::files($dir);
                    foreach ($files as $f) {
                        $contents = File::get($f->getPathname());
                        if (strpos($contents, (string) $doctor->id) !== false) {
                            // best-effort: delete session file
                            @unlink($f->getPathname());
                        }
                    }
                }
                return;
            }

            // For other drivers (redis, memcached) attempt DB fallback: delete by payload
            DB::table(Config::get('session.table', 'sessions'))
                ->where('payload', 'like', '%' . $doctor->id . '%')
                ->delete();
        } catch (\Exception $e) {
            // don't block login on cleanup failure; just continue
        }
    }

    // Note: Health facilities and schools no longer login via VoiceFlow OTP
    // Staff members must use personal emails through the invitation system
    // The getOrCreateUserForHealthFacility and getOrCreateUserForSchool methods
    // have been removed as they created accounts with entity emails
}
